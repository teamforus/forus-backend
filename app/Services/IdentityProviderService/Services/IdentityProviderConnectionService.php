<?php

namespace App\Services\IdentityProviderService\Services;

use App\Models\Identity;
use App\Models\Organization;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderConflictException;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderErrorCode;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderException;
use App\Services\IdentityProviderService\Implementations\EntraAdapter;
use App\Services\IdentityProviderService\Models\IdentityProviderAdminSession;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Models\IdentityProviderTenantReservation;
use App\Services\IdentityProviderService\Queries\IdentityProviderAdminSessionQuery;
use App\Services\IdentityProviderService\Queries\IdentityProviderConnectionQuery;
use App\Services\IdentityProviderService\Queries\IdentityProviderTenantReservationQuery;
use App\Services\OpenIdService\OpenIdException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Random\RandomException;
use Throwable;

class IdentityProviderConnectionService
{
    /**
     * @param EntraAdapter $entraAdapter
     * @param IdentityProviderAccessService $accessService
     * @param IdentityProviderAccountLinkService $accountLinkService
     * @param IdentityProviderSessionService $sessionService
     */
    public function __construct(
        protected EntraAdapter $entraAdapter,
        protected IdentityProviderAccessService $accessService,
        protected IdentityProviderAccountLinkService $accountLinkService,
        protected IdentityProviderSessionService $sessionService,
    ) {
    }

    /**
     * @param mixed $state
     * @return ?IdentityProviderAdminSession
     */
    public function findAdminSessionByState(mixed $state): ?IdentityProviderAdminSession
    {
        if (!is_string($state) || $state === '') {
            return null;
        }

        return IdentityProviderAdminSession::where('oidc_state_hash', $this->hashToken($state))
            ->first();
    }

    /**
     * @param Organization $organization
     * @param Identity $identity
     * @param string $finalUrl
     * @throws Throwable
     * @return IdentityProviderAdminSession
     */
    public function startConnectionConsent(
        Organization $organization,
        Identity $identity,
        string $finalUrl,
    ): IdentityProviderAdminSession {
        $this->accessService->assertSsoAllowed($organization);

        $authorization = $this->entraAdapter->buildAuthorization();

        return DB::transaction(function () use ($organization, $identity, $authorization, $finalUrl) {
            $organization = Organization::lockForUpdate()->findOrFail($organization->id);
            $this->accessService->assertSsoAllowed($organization);

            if (!$organization->isOwner($identity)) {
                throw new IdentityProviderException(
                    IdentityProviderErrorCode::ADMIN_CONSENT_OWNER_CHANGED,
                    __('exceptions.identity_providers.organization_owner_changed'),
                );
            }

            if ($organization->identity_provider_connection()->exists()) {
                throw new IdentityProviderConflictException(IdentityProviderErrorCode::CONNECTION_ALREADY_EXISTS);
            }

            return IdentityProviderAdminSession::create([
                'organization_id' => $organization->id,
                'previous_connection_id' => $organization->identity_provider_connections()->latest('id')->value('id'),
                'requested_by_identity_id' => $identity->id,
                'final_url' => $finalUrl,
                'status' => IdentityProviderAdminSession::STATUS_VERIFYING_TENANT,
                'oidc_state_hash' => $this->hashToken($authorization->state),
                'oidc_nonce' => $authorization->nonce,
                'oidc_code_verifier' => $authorization->codeVerifier,
                'oidc_authorization_url' => $authorization->redirectUrl,
                'expires_at' => now()->addMinutes((int) Config::get('identity_providers.admin_session_minutes', 30)),
            ]);
        }, 3);
    }

    /**
     * @param IdentityProviderAdminSession $session
     * @param Request $request
     * @throws IdentityProviderException
     * @throws OpenIdException|Throwable
     * @return array
     */
    public function resolveAdminTenant(IdentityProviderAdminSession $session, Request $request): array
    {
        $state = $request->query('state');
        $this->accessService->assertSsoAllowed($session->organization);

        if (!$session->isVerifyingTenant() || $session->isExpired()) {
            $errorCode = $session->isExpired()
                ? IdentityProviderErrorCode::ENTRA_SESSION_EXPIRED
                : IdentityProviderErrorCode::ENTRA_SESSION_INVALID;

            $session->markExpired();

            throw new IdentityProviderException(
                $errorCode,
                $errorCode === IdentityProviderErrorCode::ENTRA_SESSION_EXPIRED
                    ? 'The Entra connection session expired.'
                    : 'The Entra connection session is no longer pending.',
            );
        }

        try {
            $administrator = $this->entraAdapter->resolveAdministrator($request, [
                'state' => $state,
                'nonce' => (string) $session->oidc_nonce,
                'code_verifier' => (string) $session->oidc_code_verifier,
            ]);

            $tenantId = $administrator['tenant_id'];

            $tenantConflict = IdentityProviderConnectionQuery::whereCurrentEntraTenant(
                IdentityProviderConnection::query(),
                $tenantId,
            )->exists();

            $reservationConflict = IdentityProviderTenantReservationQuery::whereEntraTenant(
                IdentityProviderTenantReservation::query(),
                $tenantId,
            )->where('organization_id', '!=', $session->organization_id)->exists();

            if ($tenantConflict || $reservationConflict) {
                throw new IdentityProviderException(
                    IdentityProviderErrorCode::ENTRA_TENANT_ALREADY_CONNECTED,
                    $tenantConflict
                        ? 'The Entra tenant already has a connection.'
                        : 'The Entra tenant is reserved for another organization.',
                );
            }

            $consentState = $this->randomToken();

            $updated = IdentityProviderAdminSession::whereKey($session->id)
                ->where('status', IdentityProviderAdminSession::STATUS_VERIFYING_TENANT)
                ->update([
                    'status' => IdentityProviderAdminSession::STATUS_AWAITING_CONSENT,
                    'consent_state_hash' => $this->hashToken($consentState),
                    'expected_tenant_id' => $tenantId,
                    'admin_object_id' => $administrator['object_id'],
                    'tenant_verified_at' => now(),
                ]);

            if (!$updated) {
                throw new IdentityProviderException(
                    IdentityProviderErrorCode::ENTRA_SESSION_INVALID,
                    'The Entra connection session is no longer awaiting tenant verification.',
                );
            }

            return [
                'session' => $session->refresh(),
                'redirect_url' => $this->entraAdapter->adminConsentUrl($tenantId, $consentState),
            ];
        } catch (Throwable $exception) {
            $errorCode = $exception instanceof IdentityProviderException
                ? $exception->errorCode()
                : IdentityProviderErrorCode::TENANT_VERIFICATION_FAILED;

            IdentityProviderAdminSession::whereKey($session->id)
                ->where('status', IdentityProviderAdminSession::STATUS_VERIFYING_TENANT)
                ->update([
                    'status' => IdentityProviderAdminSession::STATUS_ERROR,
                    'error_code' => $errorCode,
                ]);

            throw $exception;
        }
    }

    /**
     * @param mixed $state
     * @return ?IdentityProviderAdminSession
     */
    public function findAdminConsentSession(mixed $state): ?IdentityProviderAdminSession
    {
        if (!is_string($state) || $state === '') {
            return null;
        }

        return IdentityProviderAdminSession::where('consent_state_hash', $this->hashToken($state))
            ->first();
    }

    /**
     * @param IdentityProviderAdminSession $session
     * @param Request $request
     * @throws Throwable
     * @return IdentityProviderAdminSession
     */
    public function resolveAdminConsent(
        IdentityProviderAdminSession $session,
        Request $request,
    ): IdentityProviderAdminSession {
        try {
            $result = DB::transaction(function () use ($session, $request) {
                $session = IdentityProviderAdminSession::lockForUpdate()->findOrFail($session->id);

                if (!$session->isAwaitingConsent()) {
                    throw new IdentityProviderException(
                        IdentityProviderErrorCode::ADMIN_CONSENT_SESSION_REPLAYED,
                        'The Entra admin-consent session was already used.',
                    );
                }

                $organization = Organization::lockForUpdate()->findOrFail($session->organization_id);
                $identity = $session->requested_by;
                $this->accessService->assertSsoAllowed($organization);

                if (!$identity || !$organization->isOwner($identity)) {
                    throw new IdentityProviderException(
                        IdentityProviderErrorCode::ADMIN_CONSENT_OWNER_CHANGED,
                        'The identity that started the Entra connection is no longer the organization owner.',
                    );
                }

                $this->entraAdapter->validateAdminConsent($request, $session->expected_tenant_id);

                $previousConnection = $organization->identity_provider_connections()
                    ->latest('id')
                    ->lockForUpdate()
                    ->first();

                if (
                    $previousConnection?->id !== $session->previous_connection_id ||
                    ($previousConnection &&
                        $previousConnection->status !== IdentityProviderConnection::STATUS_DISCONNECTED)
                ) {
                    throw new IdentityProviderConflictException(IdentityProviderErrorCode::CONNECTION_CHANGED_CONCURRENTLY);
                }

                $session->consented_at = now();

                $tenantReservation = IdentityProviderTenantReservationQuery::whereEntraTenant(
                    IdentityProviderTenantReservation::query(),
                    $session->expected_tenant_id,
                )
                    ->lockForUpdate()
                    ->first();

                if ($tenantReservation && (int) $tenantReservation->organization_id !== $organization->id) {
                    throw new IdentityProviderConflictException(IdentityProviderErrorCode::ENTRA_TENANT_ALREADY_CONNECTED);
                }

                $tenantConflict = IdentityProviderConnectionQuery::whereCurrentEntraTenant(
                    IdentityProviderConnection::query(),
                    $session->expected_tenant_id,
                )
                    ->lockForUpdate()
                    ->exists();

                if ($tenantConflict) {
                    throw new IdentityProviderConflictException(IdentityProviderErrorCode::ENTRA_TENANT_ALREADY_CONNECTED);
                }

                if ($session->isExpired()) {
                    throw new IdentityProviderException(
                        IdentityProviderErrorCode::ADMIN_CONSENT_SESSION_EXPIRED,
                        'The Entra admin-consent session expired.',
                    );
                }

                $connection = new IdentityProviderConnection([
                    'organization_id' => $organization->id,
                    'provider' => IdentityProviderConnection::PROVIDER_ENTRA,
                    'created_by_identity_id' => $identity->id,
                ]);

                $connection->fill([
                    'tenant_id' => $session->expected_tenant_id,
                    'issuer' => $this->entraAdapter->issuer($session->expected_tenant_id),
                    'status' => IdentityProviderConnection::STATUS_ENABLED,
                    'updated_by_identity_id' => $identity->id,
                    'consented_at' => $session->consented_at,
                    'enabled_at' => now(),
                    'paused_at' => null,
                    'disconnected_at' => null,
                ])->save();

                if ($tenantReservation) {
                    $tenantReservation->update([
                        'connection_id' => $connection->id,
                        'last_connected_at' => now(),
                    ]);
                } else {
                    IdentityProviderTenantReservation::create([
                        'organization_id' => $organization->id,
                        'connection_id' => $connection->id,
                        'provider' => IdentityProviderConnection::PROVIDER_ENTRA,
                        'tenant_id' => $session->expected_tenant_id,
                        'first_connected_at' => now(),
                        'last_connected_at' => now(),
                    ]);
                }

                $session->update([
                    'consented_at' => $session->consented_at,
                    'connection_id' => $connection->id,
                    'status' => IdentityProviderAdminSession::STATUS_CONFIRMED,
                    'confirmed_at' => now(),
                ]);

                $connection->recordEvent(
                    IdentityProviderConnection::EVENT_CONNECTION_CONNECTED,
                    identity: $identity,
                    context: [
                        'tenant_id' => $connection->tenant_id,
                    ],
                );

                return $session->refresh();
            }, 3);
        } catch (IdentityProviderException $exception) {
            if ($exception->errorCode() !== IdentityProviderErrorCode::ADMIN_CONSENT_SESSION_REPLAYED) {
                IdentityProviderAdminSession::whereKey($session->id)
                    ->where('status', IdentityProviderAdminSession::STATUS_AWAITING_CONSENT)
                    ->update([
                        'status' => $exception->errorCode() === IdentityProviderErrorCode::ADMIN_CONSENT_SESSION_EXPIRED
                            ? IdentityProviderAdminSession::STATUS_EXPIRED
                            : IdentityProviderAdminSession::STATUS_ERROR,
                        'error_code' => $exception->errorCode(),
                    ]);
            }

            throw $exception;
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                throw new IdentityProviderConflictException(IdentityProviderErrorCode::CONNECTION_CHANGED_CONCURRENTLY, $exception);
            }

            throw $exception;
        }

        return $result;
    }

    /**
     * @param IdentityProviderConnection $connection
     * @param Identity $identity
     * @throws Throwable
     * @return IdentityProviderConnection
     */
    public function pause(IdentityProviderConnection $connection, Identity $identity): IdentityProviderConnection
    {
        return DB::transaction(function () use ($connection, $identity) {
            $connection = IdentityProviderConnection::lockForUpdate()->findOrFail($connection->id);
            $this->accessService->assertSsoAllowed($connection->organization);

            if (!$connection->isEnabled()) {
                throw new IdentityProviderConflictException(IdentityProviderErrorCode::CONNECTION_NOT_ENABLED);
            }

            $this->sessionService->revokeProxies($connection);

            $connection->update([
                'status' => IdentityProviderConnection::STATUS_PAUSED,
                'updated_by_identity_id' => $identity->id,
                'paused_at' => now(),
            ]);

            $connection->recordEvent(IdentityProviderConnection::EVENT_CONNECTION_PAUSED, identity: $identity);

            return $connection->refresh();
        });
    }

    /**
     * @param IdentityProviderConnection $connection
     * @param Identity $identity
     * @throws Throwable
     * @return IdentityProviderConnection
     */
    public function resume(IdentityProviderConnection $connection, Identity $identity): IdentityProviderConnection
    {
        return DB::transaction(function () use ($connection, $identity) {
            $connection = IdentityProviderConnection::lockForUpdate()->findOrFail($connection->id);
            $this->accessService->assertSsoAllowed($connection->organization);

            if ($connection->status !== IdentityProviderConnection::STATUS_PAUSED) {
                throw new IdentityProviderConflictException(IdentityProviderErrorCode::CONNECTION_NOT_PAUSED);
            }

            $connection->update([
                'status' => IdentityProviderConnection::STATUS_ENABLED,
                'updated_by_identity_id' => $identity->id,
                'enabled_at' => now(),
                'paused_at' => null,
                'disconnected_at' => null,
            ]);

            $connection->recordEvent(IdentityProviderConnection::EVENT_CONNECTION_RESUMED, identity: $identity);

            return $connection->refresh();
        });
    }

    /**
     * @param IdentityProviderConnection $connection
     * @param Identity $identity
     * @throws Throwable
     * @return IdentityProviderConnection
     */
    public function disconnect(IdentityProviderConnection $connection, Identity $identity): IdentityProviderConnection
    {
        return DB::transaction(function () use ($connection, $identity) {
            $organization = Organization::lockForUpdate()->findOrFail($connection->organization_id);
            $connection = IdentityProviderConnection::lockForUpdate()->findOrFail($connection->id);
            $this->accessService->assertSsoAllowed($organization);

            if (!$organization->isOwner($identity)) {
                throw new IdentityProviderException(
                    IdentityProviderErrorCode::ADMIN_CONSENT_OWNER_CHANGED,
                    __('exceptions.identity_providers.organization_owner_changed'),
                );
            }

            if ($connection->status === IdentityProviderConnection::STATUS_DISCONNECTED) {
                throw new IdentityProviderConflictException(IdentityProviderErrorCode::CONNECTION_NOT_CURRENT);
            }

            $memberships = $connection->memberships_current()->lockForUpdate()->get();

            foreach ($memberships as $membership) {
                $this->accountLinkService->retireForDisconnection($membership, $identity, $connection);
            }

            $this->sessionService->invalidateOidcSessions($connection);
            $this->sessionService->revokeProxies($connection);

            IdentityProviderAdminSessionQuery::whereUnfinished(
                IdentityProviderAdminSession::where('organization_id', $organization->id),
            )->update([
                'status' => IdentityProviderAdminSession::STATUS_ERROR,
                'error_code' => IdentityProviderErrorCode::CONNECTION_DISCONNECTED,
            ]);

            $connection->update([
                'status' => IdentityProviderConnection::STATUS_DISCONNECTED,
                'updated_by_identity_id' => $identity->id,
                'disconnected_at' => now(),
            ]);

            $connection->recordEvent(
                IdentityProviderConnection::EVENT_CONNECTION_DISCONNECTED,
                identity: $identity,
                context: ['retired_memberships' => $memberships->count()],
            );

            return $connection->refresh();
        }, 3);
    }

    /**
     * @param int $bytes
     * @throws RandomException
     * @return string
     */
    protected function randomToken(int $bytes = 32): string
    {
        return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
    }

    /**
     * @param string $token
     * @return string
     */
    protected function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
