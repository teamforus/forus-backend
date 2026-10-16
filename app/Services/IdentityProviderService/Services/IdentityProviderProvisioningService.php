<?php

namespace App\Services\IdentityProviderService\Services;

use App\Models\Employee;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Identity;
use App\Models\IdentityEmail;
use App\Models\ProfileRecord;
use App\Models\Voucher;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderScimException;
use App\Services\IdentityProviderService\Models\ExternalIdentity;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Models\IdentityProviderMembership;
use App\Services\IdentityProviderService\Queries\ExternalIdentityQuery;
use App\Services\IdentityProviderService\Queries\IdentityProviderMembershipQuery;
use App\Services\IdentityProviderService\Resources\IdentityProviderScimUserResource;
use App\Services\IdentityProviderService\Support\IdentityProviderScimDiscovery;
use App\Services\IdentityProviderService\Support\IdentityProviderScimUserPayload;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

class IdentityProviderProvisioningService
{
    /**
     * @param IdentityProviderConnection $connection
     * @param array $payload
     * @param string $accountType
     * @throws Throwable
     * @return IdentityProviderMembership
     */
    public function create(
        IdentityProviderConnection $connection,
        array $payload,
        string $accountType,
    ): IdentityProviderMembership {
        return $this->transaction($connection, 'POST', $payload, null, function () use (
            $connection,
            $payload,
            $accountType,
        ): IdentityProviderMembership {
            $connection = $this->lockConnection($connection);
            $attributes = IdentityProviderScimUserPayload::normalize($payload, $connection->usesScimProfiles($accountType));

            $externalIdentity = ExternalIdentityQuery::whereEntraObject(
                ExternalIdentity::query(),
                $connection->tenant_id,
                $attributes['external_id'],
            )->lockForUpdate()->first();

            $membership = $externalIdentity
                ? $this->resolveDeletedMembership($connection, $externalIdentity, $accountType)
                : null;

            $this->assertEmailAvailable($attributes['email'], $membership?->identity);
            $this->assertUsernameAvailable($connection, $accountType, $attributes['user_name'], $membership);

            if ($membership) {
                // Reprovision the deleted account using its existing identity.
                $this->applyAttributes($connection, $membership, $attributes, $this->currentUser($connection, $membership));

                $this->changeStatus($connection, $membership, $attributes['active']
                    ? IdentityProviderMembership::PROVISIONING_STATUS_ACTIVE
                    : IdentityProviderMembership::PROVISIONING_STATUS_DISABLED);

                $connection->recordEvent(
                    $membership->isEmployee()
                        ? IdentityProviderConnection::EVENT_SCIM_EMPLOYEE_REPROVISIONED
                        : IdentityProviderConnection::EVENT_SCIM_REQUESTER_REPROVISIONED,
                    membership: $membership,
                    context: [
                        ...IdentityProviderScimUserPayload::eventContext($payload),
                        'identity_id' => $membership->identity_id,
                    ],
                );

                return $membership->refresh();
            }

            $identity = Identity::build(
                type: $accountType === IdentityProviderMembership::ACCOUNT_TYPE_EMPLOYEE ? Identity::TYPE_EMPLOYEE : null,
                organizationId: $connection->organization_id,
            );

            $identity->addEmail($attributes['email'], verified: true, primary: true, initial: true);

            /** @var Employee|null $employee */
            $employee = $accountType === IdentityProviderMembership::ACCOUNT_TYPE_EMPLOYEE
                ? $connection->organization->employees()->create(['identity_address' => $identity->address])
                : null;

            $externalIdentity = ExternalIdentity::create([
                'provider' => $connection->provider,
                'tenant_id' => $connection->tenant_id,
                'object_id' => $attributes['external_id'],
                'identity_id' => $identity->id,
            ]);

            $membership = $connection->memberships()->create([
                'external_identity_id' => $externalIdentity->id,
                'identity_id' => $identity->id,
                'employee_id' => $employee?->id,
                'account_type' => $accountType,
                'provisioning_status' => $attributes['active']
                    ? IdentityProviderMembership::PROVISIONING_STATUS_ACTIVE
                    : IdentityProviderMembership::PROVISIONING_STATUS_DISABLED,
                'scim_user_name' => $attributes['user_name'],
                'claim_state' => IdentityProviderMembership::CLAIM_CLAIMED,
            ]);

            if ($employee) {
                $employee->log(Employee::EVENT_CREATED, [
                    'employee' => $employee,
                    'organization' => $connection->organization,
                ], useRequestIdentity: false);

                if (!$attributes['active']) {
                    $employee->deleteWithReason(Employee::DELETION_REASON_PROVISIONING);
                }
            }

            if ($connection->usesScimProfiles($accountType)) {
                $identity->profiles()->create(['organization_id' => $connection->organization_id])->updateRecords([
                    'given_name' => $attributes['given_name'],
                    'family_name' => $attributes['family_name'],
                    'birth_date' => $attributes['birth_date'],
                    'gender' => IdentityProviderScimUserPayload::GENDERS[$attributes['gender']] ?? '',
                ], source: ProfileRecord::SOURCE_ENTRA);
            }

            $connection->recordEvent(
                $employee
                    ? IdentityProviderConnection::EVENT_SCIM_EMPLOYEE_CREATED
                    : IdentityProviderConnection::EVENT_SCIM_REQUESTER_CREATED,
                membership: $membership,
                context: [
                    ...IdentityProviderScimUserPayload::eventContext($payload),
                    'identity_id' => $identity->id,
                    'active' => $attributes['active'],
                ],
            );

            return $membership;
        }, $accountType);
    }

    /**
     * @param IdentityProviderConnection $connection
     * @param string $uid
     * @param array $payload
     * @param string $accountType
     * @param bool $patch
     * @throws Throwable
     * @return IdentityProviderMembership
     */
    public function update(
        IdentityProviderConnection $connection,
        string $uid,
        array $payload,
        string $accountType,
        bool $patch = false,
    ): IdentityProviderMembership {
        return $this->transaction($connection, $patch ? 'PATCH' : 'PUT', $payload, $uid, function () use (
            $connection,
            $uid,
            $payload,
            $accountType,
            $patch,
        ): IdentityProviderMembership {
            $connection = $this->lockConnection($connection);

            $membership = IdentityProviderMembershipQuery::forScimConnection($connection, $accountType)
                ->where('uid', $uid)->lockForUpdate()->firstOrFail();

            $identity = Identity::lockForUpdate()->findOrFail($membership->identity_id);
            $current = $this->currentUser($connection, $membership);
            $withProfiles = $connection->usesScimProfiles($accountType);

            $attributes = $patch
                ? IdentityProviderScimUserPayload::normalizePatch($payload, $current, $withProfiles)
                : IdentityProviderScimUserPayload::normalize($payload, $withProfiles, $current['externalId']);

            $this->assertEmailAvailable($attributes['email'], $identity);
            $this->assertUsernameAvailable($connection, $accountType, $attributes['user_name'], $membership);

            $this->applyAttributes($connection, $membership, $attributes, $current);

            $this->changeStatus($connection, $membership, $attributes['active']
                ? IdentityProviderMembership::PROVISIONING_STATUS_ACTIVE
                : IdentityProviderMembership::PROVISIONING_STATUS_DISABLED);

            return $membership->refresh();
        }, $accountType);
    }

    /**
     * @param IdentityProviderConnection $connection
     * @param string $uid
     * @param string $accountType
     * @throws Throwable
     * @return void
     */
    public function delete(IdentityProviderConnection $connection, string $uid, string $accountType): void
    {
        $this->transaction($connection, 'DELETE', [], $uid, function () use ($connection, $uid, $accountType): void {
            $connection = $this->lockConnection($connection);

            $membership = IdentityProviderMembershipQuery::forScimConnection(
                $connection,
                $accountType,
                withDeleted: true,
            )->where('uid', $uid)->lockForUpdate()->firstOrFail();

            if ($membership->isProvisioningDeleted()) {
                return;
            }

            $membership->setRelation('identity', Identity::lockForUpdate()->findOrFail($membership->identity_id));
            $this->changeStatus($connection, $membership, IdentityProviderMembership::PROVISIONING_STATUS_DELETED);
        }, $accountType);
    }

    /**
     * @template TResult
     * @param IdentityProviderConnection $connection
     * @param string $method
     * @param array $payload
     * @param string|null $uid
     * @param Closure(): TResult $callback
     * @param string $accountType
     * @throws IdentityProviderScimException
     * @return TResult
     */
    protected function transaction(
        IdentityProviderConnection $connection,
        string $method,
        array $payload,
        ?string $uid,
        Closure $callback,
        string $accountType,
    ): mixed {
        try {
            return DB::transaction($callback);
        } catch (Throwable $exception) {
            throw resolve(IdentityProviderLogService::class)->scimMutationFailure(
                $connection,
                $accountType,
                $method,
                $payload,
                $uid,
                $exception,
            );
        }
    }

    /**
     * @param IdentityProviderConnection $connection
     * @param IdentityProviderMembership $membership
     * @param string $status
     * @throws Throwable
     * @return void
     */
    protected function changeStatus(
        IdentityProviderConnection $connection,
        IdentityProviderMembership $membership,
        string $status,
    ): void {
        if ($membership->provisioning_status === $status) {
            return;
        }

        $wasActive = $membership->isProvisioningActive();
        $membership->update(['provisioning_status' => $status]);

        $isActive = $membership->isProvisioningActive();
        $isDeactivating = $wasActive && !$isActive;
        $isReactivating = !$wasActive && $isActive;
        $cleared = [];

        if ($membership->isEmployee()) {
            $employee = Employee::withTrashed()->lockForUpdate()->findOrFail($membership->employee_id);

            if ($isReactivating) {
                if (!$employee->trashed() || $employee->deletion_reason !== Employee::DELETION_REASON_PROVISIONING) {
                    throw new IdentityProviderScimException(
                        __('identity_provider.scim.account_conflict'),
                        409,
                        'uniqueness',
                        'account_conflict',
                    );
                }

                $employee->deletion_reason = null;
                $employee->restore();
            } elseif (!$isActive) {
                $cleared = $this->clearEmployeeAssignments($employee);

                if (!$employee->trashed()) {
                    $employee->deleteWithReason(Employee::DELETION_REASON_PROVISIONING);
                }
            }
        }

        if (!$isActive) {
            $sessionService = resolve(IdentityProviderSessionService::class);
            $sessionService->revokeProxies($membership);
            $sessionService->invalidateOidcSessions($membership);
        }

        if ($isDeactivating) {
            // Deactivate all active and pending vouchers, including child vouchers.
            $vouchers = $membership->identity->vouchers()
                ->where('state', '!=', Voucher::STATE_DEACTIVATED)
                ->orderByDesc('id')->lockForUpdate()->get();

            foreach ($vouchers as $voucher) {
                $voucher->deactivate(
                    note: __('identity_provider.scim.voucher_deactivated'),
                    source: $connection->provider,
                );
            }
        } elseif ($isReactivating) {
            // Restore all deactivated vouchers, including those previously disabled manually.
            $vouchers = $membership->identity->vouchers()
                ->where('state', Voucher::STATE_DEACTIVATED)
                ->orderByDesc('id')->lockForUpdate()->get();

            foreach ($vouchers as $voucher) {
                $voucher->activateAsSystem(__('identity_provider.scim.voucher_reactivated'), $connection->provider);
            }
        }

        $event = $membership->isEmployee() ? match ($status) {
            IdentityProviderMembership::PROVISIONING_STATUS_ACTIVE =>
                IdentityProviderConnection::EVENT_SCIM_EMPLOYEE_REACTIVATED,
            IdentityProviderMembership::PROVISIONING_STATUS_DISABLED =>
                IdentityProviderConnection::EVENT_SCIM_EMPLOYEE_DISABLED,
            IdentityProviderMembership::PROVISIONING_STATUS_DELETED =>
                IdentityProviderConnection::EVENT_SCIM_EMPLOYEE_DELETED,
        } : match ($status) {
            IdentityProviderMembership::PROVISIONING_STATUS_ACTIVE =>
                IdentityProviderConnection::EVENT_SCIM_REQUESTER_REACTIVATED,
            IdentityProviderMembership::PROVISIONING_STATUS_DISABLED =>
                IdentityProviderConnection::EVENT_SCIM_REQUESTER_DISABLED,
            IdentityProviderMembership::PROVISIONING_STATUS_DELETED =>
                IdentityProviderConnection::EVENT_SCIM_REQUESTER_DELETED,
        };

        $connection->recordEvent($event, membership: $membership, context: [
            'identity_id' => $membership->identity_id,
            ...$cleared ? ['cleared_assignments' => $cleared] : [],
        ]);
    }

    /**
     * @param Employee $employee
     * @return array
     */
    protected function clearEmployeeAssignments(Employee $employee): array
    {
        $requests = FundRequest::where('employee_id', $employee->id)
            ->where('state', FundRequest::STATE_PENDING)->lockForUpdate()->get();

        foreach ($requests as $fundRequest) {
            $fundRequest->resignEmployee($employee);
        }

        return [
            'fund_requests' => $requests->count(),
            'default_validators' => Fund::where('default_validator_employee_id', $employee->id)
                ->update(['default_validator_employee_id' => null]),
        ];
    }

    /**
     * @param IdentityProviderConnection $connection
     * @param IdentityProviderMembership $membership
     * @return array
     */
    protected function currentUser(
        IdentityProviderConnection $connection,
        IdentityProviderMembership $membership,
    ): array {
        $membership->load(IdentityProviderScimUserResource::loadForConnection($connection, $membership->account_type));

        return IdentityProviderScimUserResource::make($membership)->resolve();
    }

    /**
     * @param IdentityProviderConnection $connection
     * @param IdentityProviderMembership $membership
     * @param array $attributes
     * @param array $current
     * @return void
     */
    protected function applyAttributes(
        IdentityProviderConnection $connection,
        IdentityProviderMembership $membership,
        array $attributes,
        array $current,
    ): void {
        $changedFields = [];

        if ($current['emails'][0]['value'] !== $attributes['email']) {
            $this->replaceEmail($connection, $membership, $attributes['email']);
            $changedFields[] = 'emails';
        }

        if ($membership->scim_user_name !== $attributes['user_name']) {
            $membership->scim_user_name = $attributes['user_name'];
            $changedFields[] = 'userName';
        }

        if ($connection->usesScimProfiles($membership->account_type)) {
            foreach (['given_name' => 'givenName', 'family_name' => 'familyName'] as $key => $name) {
                if ($current['name'][$name] !== $attributes[$key]) {
                    $changedFields[] = 'name.' . $name;
                }
            }

            foreach (['birth_date' => 'birthDate', 'gender' => 'gender'] as $key => $name) {
                if (($current[IdentityProviderScimDiscovery::SCHEMA_FORUS_USER][$name] ?? '') !== $attributes[$key]) {
                    $changedFields[] = $name;
                }
            }

            $membership->identity->profiles()->firstOrCreate([
                'organization_id' => $connection->organization_id,
            ])->updateRecords([
                'given_name' => $attributes['given_name'],
                'family_name' => $attributes['family_name'],
                'birth_date' => $attributes['birth_date'],
                'gender' => IdentityProviderScimUserPayload::GENDERS[$attributes['gender']] ?? '',
            ], source: ProfileRecord::SOURCE_ENTRA);
        }

        if ($changedFields) {
            $membership->updated_at = now();
            $membership->save();

            $connection->recordEvent(
                $membership->isEmployee()
                    ? IdentityProviderConnection::EVENT_SCIM_EMPLOYEE_UPDATED
                    : IdentityProviderConnection::EVENT_SCIM_REQUESTER_UPDATED,
                membership: $membership,
                context: ['identity_id' => $membership->identity_id, 'changed_fields' => $changedFields],
            );
        }
    }

    /**
     * @param IdentityProviderConnection $connection
     * @throws IdentityProviderScimException
     * @return IdentityProviderConnection
     */
    protected function lockConnection(IdentityProviderConnection $connection): IdentityProviderConnection
    {
        $connection = IdentityProviderConnection::lockForUpdate()->findOrFail($connection->id);

        if ($connection->isDisconnected()) {
            throw new IdentityProviderScimException(
                __('identity_provider.scim.connection_unavailable'),
                409,
                reason: 'connection_unavailable',
            );
        }

        return $connection;
    }

    /**
     * @param string $email
     * @param Identity|null $identity
     * @throws IdentityProviderScimException
     * @return void
     */
    protected function assertEmailAvailable(string $email, ?Identity $identity = null): void
    {
        if (IdentityEmail::where('email', $email)
            ->when($identity, fn (Builder $query) => $query->where('identity_address', '!=', $identity->address))
            ->exists()) {
            throw new IdentityProviderScimException(
                __('identity_provider.scim.email_conflict'),
                409,
                'uniqueness',
                'email_conflict',
            );
        }
    }

    /**
     * @param IdentityProviderConnection $connection
     * @param string $accountType
     * @param string $username
     * @param IdentityProviderMembership|null $membership
     * @throws IdentityProviderScimException
     * @return void
     */
    protected function assertUsernameAvailable(
        IdentityProviderConnection $connection,
        string $accountType,
        string $username,
        ?IdentityProviderMembership $membership = null,
    ): void {
        $query = IdentityProviderMembershipQuery::forScimConnection($connection, $accountType)
            ->when($membership, fn (Builder $query) => $query->where('id', '!=', $membership->id));

        if (IdentityProviderMembershipQuery::whereScimFilter($query, [
            'attribute' => 'username', 'value' => $username,
        ])->exists()) {
            throw new IdentityProviderScimException(
                __('identity_provider.scim.username_conflict'),
                409,
                'uniqueness',
                'username_conflict',
            );
        }
    }

    /**
     * @param IdentityProviderConnection $connection
     * @param IdentityProviderMembership $membership
     * @param string $value
     * @return void
     */
    protected function replaceEmail(
        IdentityProviderConnection $connection,
        IdentityProviderMembership $membership,
        string $value,
    ): void {
        $email = $membership->identity->primary_email;
        $previousEmail = $email->email;

        $email->update(['email' => $value, 'verified' => true]);
        $email->setPrimary();

        $membership->identity->unsetRelation('emails')->unsetRelation('primary_email');

        $connection->recordEvent(
            $membership->isEmployee()
                ? IdentityProviderConnection::EVENT_SCIM_EMPLOYEE_EMAIL_CHANGED
                : IdentityProviderConnection::EVENT_SCIM_REQUESTER_EMAIL_CHANGED,
            membership: $membership,
            context: [
                'identity_id' => $membership->identity_id,
                'email_change' => ['old' => $previousEmail, 'new' => $value],
            ],
        );
    }

    /**
     * @param IdentityProviderConnection $connection
     * @param ExternalIdentity $externalIdentity
     * @param string $accountType
     * @throws IdentityProviderScimException
     * @return IdentityProviderMembership
     */
    protected function resolveDeletedMembership(
        IdentityProviderConnection $connection,
        ExternalIdentity $externalIdentity,
        string $accountType,
    ): IdentityProviderMembership {
        $membership = $externalIdentity->memberships()->where('connection_id', $connection->id)
            ->lockForUpdate()->first();

        if (!$membership || $membership->account_type !== $accountType || !$membership->isClaimed() ||
            !$membership->isProvisioningDeleted() || !$membership->identity_id ||
            ($membership->isRequester() && $membership->employee_id !== null) ||
            $membership->identity_id !== $externalIdentity->identity_id) {
            throw new IdentityProviderScimException(
                __('identity_provider.scim.account_conflict'),
                409,
                'uniqueness',
                'account_conflict',
            );
        }

        $identity = Identity::lockForUpdate()->findOrFail($membership->identity_id);

        $employee = $membership->isEmployee()
            ? $identity->employees()->onlyTrashed()->whereKey($membership->employee_id)
                ->where('deletion_reason', Employee::DELETION_REASON_PROVISIONING)
                ->where('organization_id', $connection->organization_id)->lockForUpdate()->first()
            : null;

        if (($membership->isEmployee() && !$employee) ||
            $identity->employees()->withTrashed()
                ->where(fn (Builder $query) => $query
                    ->whereNull('deleted_at')
                    ->orWhere('deletion_reason', Employee::DELETION_REASON_PROVISIONING))
                ->when($employee, fn (Builder $query, Employee $employee) => $query->where('id', '!=', $employee->id))
                ->exists() ||
            $identity->organizations()->exists() ||
            $identity->identity_provider_memberships()->where('id', '!=', $membership->id)->exists()) {
            throw new IdentityProviderScimException(
                __('identity_provider.scim.account_conflict'),
                409,
                'uniqueness',
                'account_conflict',
            );
        }

        $membership->setRelation('identity', $identity);

        return $membership;
    }
}
