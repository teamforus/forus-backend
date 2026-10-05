<?php

namespace App\Services\IdentityProviderService\Services;

use App\Models\Identity;
use App\Models\IdentityProxy;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderErrorCode;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderException;
use App\Services\IdentityProviderService\Models\ExternalIdentity;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Models\IdentityProviderMembership;
use App\Services\IdentityProviderService\Models\IdentityProviderOidcSession;
use App\Services\IdentityProviderService\Queries\ExternalIdentityQuery;
use App\Services\IdentityProviderService\Queries\IdentityProviderConnectionQuery;
use Illuminate\Support\Facades\DB;
use Throwable;

class IdentityProviderAccountLinkService
{
    public const string CONSENT_VERSION = 'entra-global-login-v1';

    /**
     * @param IdentityProviderAccessService $accessService
     * @param IdentityProviderSessionService $sessionService
     */
    public function __construct(
        protected IdentityProviderAccessService $accessService,
        protected IdentityProviderSessionService $sessionService,
    ) {
    }

    /**
     * @param IdentityProviderOidcSession $session
     * @param IdentityProxy $proxy
     * @param string $exchangeToken
     * @param string $browserToken
     * @throws Throwable
     * @return IdentityProviderMembership
     */
    public function completeSelfLink(
        IdentityProviderOidcSession $session,
        IdentityProxy $proxy,
        string $exchangeToken,
        string $browserToken,
    ): IdentityProviderMembership {
        return DB::transaction(function () use ($session, $proxy, $exchangeToken, $browserToken) {
            $session = IdentityProviderOidcSession::lockForUpdate()->findOrFail($session->id);

            if (
                $session->mode !== IdentityProviderOidcSession::MODE_SELF_LINK ||
                !$session->canExchange($exchangeToken, $browserToken) ||
                $session->isExpired() ||
                !$session->verified_account
            ) {
                throw new IdentityProviderException(
                    IdentityProviderErrorCode::ACCOUNT_LINK_INVALID,
                    __('exceptions.identity_providers.account_link_invalid'),
                );
            }

            $connection = IdentityProviderConnection::lockForUpdate()->findOrFail($session->connection_id);
            $identity = Identity::lockForUpdate()->findOrFail($session->identity_id);
            $proxy = IdentityProxy::lockForUpdate()->find($proxy->id);
            $account = $session->verified_account;

            if (
                !$proxy?->isActive() ||
                $proxy->identity_address !== $identity->address ||
                $proxy->identity_provider_binding()->exists() ||
                $connection->tenant_id !== $account['tenant_id']
            ) {
                throw new IdentityProviderException(
                    IdentityProviderErrorCode::ACCOUNT_LINK_INVALID,
                    __('exceptions.identity_providers.account_link_invalid'),
                );
            }

            $this->accessService->assertSsoActive($connection);

            if (!IdentityProviderConnectionQuery::whereAvailableForSelfLink(
                IdentityProviderConnection::whereKey($connection->id),
                $identity,
            )->exists()) {
                throw new IdentityProviderException(
                    IdentityProviderErrorCode::ACCOUNT_LINK_UNAVAILABLE,
                    __('exceptions.identity_providers.account_link_unavailable'),
                );
            }

            $employee = $connection->organization->employees()
                ->where('identity_address', $identity->address)
                ->lockForUpdate()
                ->firstOrFail();

            $externalIdentity = ExternalIdentityQuery::whereEntraObject(
                ExternalIdentity::query(),
                $account['tenant_id'],
                $account['object_id'],
            )->lockForUpdate()->first();

            $membership = $externalIdentity?->memberships()
                ->where('connection_id', $connection->id)
                ->lockForUpdate()
                ->first();

            if (
                ($externalIdentity?->identity_id &&
                    $externalIdentity->identity_id !== $identity->id) ||
                ($membership?->identity_id && $membership->identity_id !== $identity->id) ||
                ($membership?->employee_id && $membership->employee_id !== $employee->id)
            ) {
                throw new IdentityProviderException(
                    IdentityProviderErrorCode::ACCOUNT_LINK_CONFLICT,
                    __('exceptions.identity_providers.account_link_conflict'),
                );
            }

            $externalIdentity ??= ExternalIdentity::create([
                'provider' => IdentityProviderConnection::PROVIDER_ENTRA,
                'tenant_id' => $account['tenant_id'],
                'object_id' => $account['object_id'],
            ]);

            $externalIdentity->update([
                'issuer' => $account['issuer'],
                'subject' => $account['subject'],
                'identity_id' => $identity->id,
                'last_authenticated_at' => now(),
            ]);

            $membership ??= new IdentityProviderMembership([
                'connection_id' => $connection->id,
                'external_identity_id' => $externalIdentity->id,
            ]);

            $membership->fill([
                'identity_id' => $identity->id,
                'employee_id' => $employee->id,
                'claim_state' => IdentityProviderMembership::CLAIM_CLAIMED,
                'consent_version' => self::CONSENT_VERSION,
                'consented_at' => now(),
                'retired_at' => null,
            ])->save();

            $session->update([
                'membership_id' => $membership->id,
                'status' => IdentityProviderOidcSession::STATUS_RESOLVED,
                'resolved_at' => now(),
                'verified_account' => null,
                'exchange_token_hash' => null,
                'browser_token_hash' => null,
                'exchange_consumed_at' => now(),
            ]);

            $connection->recordEvent(
                IdentityProviderConnection::EVENT_ENTRA_LINK_SELF_LINKED,
                membership: $membership,
                identity: $identity,
                context: $membership->only(['identity_id', 'employee_id']),
            );

            return $membership->refresh();
        }, 3);
    }

    /**
     * @param IdentityProviderMembership $membership
     * @param Identity $identity
     * @throws Throwable
     * @return IdentityProviderMembership
     */
    public function selfUnlink(
        IdentityProviderMembership $membership,
        Identity $identity,
    ): IdentityProviderMembership {
        return DB::transaction(function () use ($membership, $identity) {
            $membership = IdentityProviderMembership::lockForUpdate()->findOrFail($membership->id);

            if (
                !$membership->isClaimed() ||
                $membership->identity_id !== $identity->id
            ) {
                throw new IdentityProviderException(
                    IdentityProviderErrorCode::LINK_NOT_ACTIVE,
                    __('exceptions.identity_providers.link_not_active'),
                );
            }

            $this->sessionService->invalidateOidcSessions($membership);
            $this->sessionService->revokeProxies($membership);

            $membership->external_identity()->lockForUpdate()->firstOrFail()->update([
                'identity_id' => null,
            ]);

            $eventContext = $membership->only(['identity_id', 'employee_id']);

            $membership->update([
                'identity_id' => null,
                'employee_id' => null,
                'claim_state' => IdentityProviderMembership::CLAIM_RETIRED,
                'retired_at' => now(),
                'consent_version' => null,
                'consented_at' => null,
            ]);

            $membership->connection->recordEvent(
                IdentityProviderConnection::EVENT_ENTRA_LINK_SELF_UNLINKED,
                membership: $membership,
                identity: $identity,
                context: $eventContext,
            );

            return $membership->refresh();
        }, 3);
    }

    /**
     * The caller holds connection and membership locks inside the disconnection transaction.
     *
     * @param IdentityProviderMembership $membership
     * @param Identity $identity
     * @param IdentityProviderConnection $connection
     * @throws Throwable
     * @return void
     */
    public function retireForDisconnection(
        IdentityProviderMembership $membership,
        Identity $identity,
        IdentityProviderConnection $connection,
    ): void {
        $this->sessionService->revokeProxies($membership);

        if ($membership->identity_id) {
            $membership->external_identity()->lockForUpdate()->firstOrFail()->update([
                'identity_id' => null,
            ]);
        }

        $eventContext = $membership->only(['identity_id', 'employee_id']);

        $membership->update([
            'identity_id' => null,
            'employee_id' => null,
            'claim_state' => IdentityProviderMembership::CLAIM_RETIRED,
            'retired_at' => now(),
        ]);

        $connection->recordEvent(
            IdentityProviderConnection::EVENT_MEMBERSHIP_RETIRED_BY_DISCONNECTION,
            membership: $membership,
            identity: $identity,
            context: $eventContext,
        );
    }
}
