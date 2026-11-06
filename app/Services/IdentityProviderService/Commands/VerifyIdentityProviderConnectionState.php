<?php

namespace App\Services\IdentityProviderService\Commands;

use App\Models\Employee;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Models\IdentityProviderMembership;
use Illuminate\Console\Command;

class VerifyIdentityProviderConnectionState extends Command
{
    protected $signature = 'identity-provider:verify-state {connection : Connection UID}';
    protected $description = 'Check stored connection memberships and report inconsistencies.';

    /**
     * @return int
     */
    public function handle(): int
    {
        $connection = IdentityProviderConnection::where('uid', $this->argument('connection'))
            ->firstOrFail();

        $issues = [];

        $connection->memberships()
            ->with(['external_identity', 'identity.identity_provider_managed_membership'])
            ->orderBy('id')
            ->each(function (IdentityProviderMembership $membership) use ($connection, &$issues): void {
                $externalIdentity = $membership->external_identity;

                if ($membership->isRetired()) {
                    if ($membership->identity_id !== null || $membership->employee_id !== null) {
                        $issues[] = [$membership->uid, 'Retired membership still has an identity or employee'];
                    }

                    if ($externalIdentity?->identity_id && !$externalIdentity->memberships()
                        ->where('claim_state', IdentityProviderMembership::CLAIM_CLAIMED)
                        ->where('identity_id', $externalIdentity->identity_id)
                        ->exists()) {
                        $issues[] = [$membership->uid, 'External account has an identity but no matching claimed membership'];
                    }

                    return;
                }

                if (!$externalIdentity ||
                    $externalIdentity->provider !== $connection->provider ||
                    $externalIdentity->tenant_id !== $connection->tenant_id) {
                    $issues[] = [$membership->uid, 'External account is missing or belongs to another provider or tenant'];
                }

                if ($externalIdentity?->identity_id !== $membership->identity_id) {
                    $issues[] = [$membership->uid, 'External account and membership identity bindings do not match'];
                }

                if ($membership->isProvisioned() &&
                    $membership->identity?->identity_provider_managed_membership?->id !== $membership->id) {
                    $issues[] = [$membership->uid, 'Managed identity membership is missing or inconsistent'];
                }

                if ($membership->isRequester()) {
                    if (
                        !$membership->isClaimed() ||
                        !$membership->identity ||
                        $membership->employee_id !== null ||
                        !$membership->isProvisioned() ||
                        $membership->scim_user_name === null ||
                        $membership->scim_user_name === ''
                    ) {
                        $issues[] = [
                            $membership->uid,
                            'Requester membership identity or provisioning fields are inconsistent',
                        ];
                    }
                } elseif ($membership->isEmployee()) {
                    $employee = $membership->employee()->withTrashed()
                        ->with('identity_provider_managed_membership')->first();

                    if ($membership->isClaimed() && (
                        $membership->identity_id === null ||
                        !$employee ||
                        $employee->organization_id !== $connection->organization_id ||
                        $employee->identity_address !== $membership->identity?->address
                    )) {
                        $issues[] = [$membership->uid, 'Linked identity or employee is missing or inconsistent'];
                    }

                    if ($membership->isProvisioned()) {
                        $shouldBeDeleted = !$membership->isProvisioningActive();
                        $expectedDeletionReason = $shouldBeDeleted
                            ? Employee::DELETION_REASON_PROVISIONING
                            : null;

                        $employeeStateMismatch = $employee && (
                            $employee->trashed() !== $shouldBeDeleted ||
                            $employee->deletion_reason !== $expectedDeletionReason
                        );

                        if (
                            !$membership->isClaimed() ||
                            !$employee ||
                            $employeeStateMismatch ||
                            $employee->identity_provider_managed_membership?->id !== $membership->id ||
                            $membership->scim_user_name === null ||
                            $membership->scim_user_name === ''
                        ) {
                            $issues[] = [
                                $membership->uid,
                                'Provisioned employee or provisioning fields are inconsistent',
                            ];
                        }
                    }
                }
            });

        if ($issues === []) {
            $this->info('No identity-provider state inconsistencies found.');
        } else {
            $this->table(['Membership', 'Issue'], $issues);
        }

        return $issues === [] ? self::SUCCESS : self::FAILURE;
    }
}
