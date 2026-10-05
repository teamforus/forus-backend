<?php

namespace App\Services\IdentityProviderService\Services;

use App\Models\Employee;
use App\Models\Identity;
use App\Models\IdentityProxy;
use App\Models\Implementation;
use App\Services\IdentityProviderService\Contracts\IdentityProviderAdapterContract;
use App\Services\IdentityProviderService\Data\IdentityProviderAccountData;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderErrorCode;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderException;
use App\Services\IdentityProviderService\Models\ExternalIdentity;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Models\IdentityProviderMembership;
use App\Services\IdentityProviderService\Models\IdentityProviderOidcSession;
use App\Services\IdentityProviderService\Models\IdentityProviderProxyBinding;
use App\Services\IdentityProviderService\Queries\ExternalIdentityQuery;
use App\Services\IdentityProviderService\Queries\IdentityProviderConnectionQuery;
use App\Services\IdentityProviderService\Queries\IdentityProviderOidcSessionQuery;
use App\Services\OpenIdService\OpenIdException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Throwable;

class IdentityProviderOidcService
{
    /**
     * @param IdentityProviderAdapterContract $adapter
     * @param IdentityProviderAccessService $accessService
     */
    public function __construct(
        protected IdentityProviderAdapterContract $adapter,
        protected IdentityProviderAccessService $accessService,
    ) {
    }

    /**
     * @param string $token
     * @param string $browserToken
     * @param string $ip
     * @throws Throwable
     * @return IdentityProxy
     */
    public function exchange(string $token, string $browserToken, string $ip): IdentityProxy
    {
        return DB::transaction(function () use ($token, $browserToken, $ip) {
            $session = IdentityProviderOidcSession::where('exchange_token_hash', $this->hashToken($token))
                ->lockForUpdate()
                ->first();

            if (
                !$session?->canExchange($token, $browserToken) ||
                !in_array($session->mode, [IdentityProviderOidcSession::MODE_DASHBOARD, IdentityProviderOidcSession::MODE_WEBSHOP], true)
            ) {
                throw new IdentityProviderException(IdentityProviderErrorCode::EXCHANGE_INVALID, 'The Entra login exchange is invalid.');
            }

            $connection = IdentityProviderConnection::lockForUpdate()->find($session->connection_id);
            $membership = IdentityProviderMembership::lockForUpdate()->find($session->membership_id);
            $employee = Employee::lockForUpdate()->find($membership?->employee_id);

            if (
                !$connection ||
                !$membership ||
                $membership->connection_id !== $connection->id ||
                $session->identity_id !== $membership->identity_id
            ) {
                throw new IdentityProviderException(IdentityProviderErrorCode::EXCHANGE_INVALID, 'The Entra login link is inactive.');
            }

            $this->accessService->assertLoginAllowed($session, $connection, $membership, $employee);
            $identity = Identity::lockForUpdate()->find($membership->identity_id);

            if (!$identity) {
                throw new IdentityProviderException(IdentityProviderErrorCode::IDENTITY_MISSING, 'The managed identity was not found.');
            }

            $proxy = Identity::makeProxy('short_token', $identity, IdentityProxy::STATE_ACTIVE);
            $proxy->update(['activated_at' => now()]);

            IdentityProviderProxyBinding::create([
                'identity_proxy_id' => $proxy->id,
                'connection_id' => $connection->id,
                'membership_id' => $membership->id,
            ]);

            $proxy->inherit2FAState($ip, (int) Config::get('forus.auth_2fa.remember_hours'));

            $session->update([
                'identity_proxy_id' => $proxy->id,
                'exchange_token_hash' => null,
                'browser_token_hash' => null,
                'exchange_consumed_at' => now(),
            ]);

            return $proxy;
        }, 3);
    }

    /**
     * @param Implementation $implementation
     * @param string $mode
     * @param ?string $target
     * @throws Throwable
     * @return array{session: IdentityProviderOidcSession, browser_token: string}
     */
    public function startLogin(Implementation $implementation, string $mode, ?string $target = null): array
    {
        $browserToken = bin2hex(random_bytes(32));

        $session = match ($mode) {
            IdentityProviderOidcSession::MODE_DASHBOARD => $this->startDashboard(),
            IdentityProviderOidcSession::MODE_WEBSHOP => $this->startWebshop($implementation, $target),
            default => throw new IdentityProviderException(
                IdentityProviderErrorCode::MODE_INVALID,
                __('exceptions.identity_providers.mode_invalid'),
            ),
        };

        $session->update(['browser_token_hash' => hash('sha256', $browserToken)]);

        return ['session' => $session, 'browser_token' => $browserToken];
    }

    /**
     * @param IdentityProviderConnection $connection
     * @param Identity $identity
     * @param string $finalUrl
     * @param string $browserToken
     * @throws OpenIdException
     * @return IdentityProviderOidcSession
     */
    public function startSelfLink(
        IdentityProviderConnection $connection,
        Identity $identity,
        string $finalUrl,
        string $browserToken,
    ): IdentityProviderOidcSession {
        $session = $this->startSession(
            IdentityProviderOidcSession::MODE_SELF_LINK,
            Implementation::general(),
            $connection,
            $finalUrl,
            identityId: $identity->id,
        );

        $session->update(['browser_token_hash' => $this->hashToken($browserToken)]);

        return $session;
    }

    /**
     * @param mixed $state
     * @return ?IdentityProviderOidcSession
     */
    public function findByState(mixed $state): ?IdentityProviderOidcSession
    {
        return is_string($state) && $state !== ''
            ? IdentityProviderOidcSession::where('state', $this->hashToken($state))->first()
            : null;
    }

    /**
     * @param IdentityProviderOidcSession $session
     * @param Request $request
     * @throws Throwable
     * @return string
     */
    public function resolveCallback(IdentityProviderOidcSession $session, Request $request): string
    {
        $connection = null;

        try {
            if (!$session->isPending() || $session->isExpired()) {
                $session->markExpired();
                throw new IdentityProviderException(IdentityProviderErrorCode::SESSION_EXPIRED, 'The Entra login session expired.');
            }

            $this->accessService->assertModuleEnabled();
            $connection = $session->connection;
            $state = $request->query('state');

            $account = $this->adapter->resolveAccount(
                $request,
                [
                    'state' => $state,
                    'nonce' => (string) $session->nonce,
                    'code_verifier' => (string) $session->code_verifier,
                ],
                $connection?->tenant_id,
            );

            $connection = $this->resolveConnection($session, $account->tenantId);
            $this->accessService->assertSsoActive($connection);

            if ($session->mode === IdentityProviderOidcSession::MODE_SELF_LINK) {
                return $this->prepareSelfLink($session, $connection, $account);
            }

            $membership = $this->resolveLoginMembership(
                $session,
                $connection,
                $account->tenantId,
                $account->externalAccountId,
            );

            return DB::transaction(function () use (
                $session,
                $connection,
                $membership,
                $account,
            ) {
                $session = IdentityProviderOidcSession::lockForUpdate()->findOrFail($session->id);

                if (!$session->isPending() || $session->isExpired()) {
                    throw new IdentityProviderException(
                        IdentityProviderErrorCode::SESSION_REPLAYED,
                        'The Entra login session was already used.',
                    );
                }

                $connection = IdentityProviderConnection::lockForUpdate()->findOrFail($connection->id);

                $membership = IdentityProviderMembership::lockForUpdate()->findOrFail($membership->id);
                $employee = Employee::lockForUpdate()->find($membership->employee_id);

                $this->accessService->assertLoginAllowed($session, $connection, $membership, $employee);

                $membership->external_identity->update([
                    'issuer' => $account->issuer,
                    'subject' => $account->subject,
                    'last_authenticated_at' => now(),
                ]);

                $session->update([
                    'connection_id' => $connection->id,
                    'membership_id' => $membership->id,
                    'identity_id' => $membership->identity_id,
                    'status' => IdentityProviderOidcSession::STATUS_RESOLVED,
                    'resolved_at' => now(),
                ]);

                $connection->update([
                    'last_auth_success_at' => now(),
                    'last_auth_failure_at' => null,
                    'last_auth_failure_code' => null,
                ]);

                $connection->recordEvent(
                    IdentityProviderConnection::EVENT_ENTRA_LOGIN_SUCCEEDED,
                    membership: $membership,
                    identity: $membership->identity,
                    context: ['mode' => $session->mode],
                );

                return url_extend_get_params($session->final_url, array_filter([
                    'entra_exchange' => $session->createExchangeToken(),
                    'entra_session' => $session->uid,
                    'target' => $session->target,
                ]));
            }, 3);
        } catch (Throwable $exception) {
            $this->fail(
                $session,
                $exception instanceof IdentityProviderException ? $exception->errorCode() : IdentityProviderErrorCode::CALLBACK_FAILED,
                $connection,
            );

            if ($exception instanceof OpenIdException) {
                throw new IdentityProviderException(IdentityProviderErrorCode::CALLBACK_FAILED, 'The Entra callback failed.', $exception);
            }

            throw $exception;
        }
    }

    /**
     * @param IdentityProviderOidcSession $session
     * @param string $errorCode
     * @param ?IdentityProviderConnection $connection
     * @return void
     */
    protected function fail(
        IdentityProviderOidcSession $session,
        string $errorCode,
        ?IdentityProviderConnection $connection,
    ): void {
        try {
            DB::transaction(function () use ($session, $errorCode, $connection): void {
                $connection ??= $session->connection()->first();

                $updated = IdentityProviderOidcSessionQuery::wherePending(IdentityProviderOidcSession::whereKey($session->id))
                    ->update([
                        'connection_id' => $connection?->id,
                        'status' => IdentityProviderOidcSession::STATUS_ERROR,
                        'resolved_at' => now(),
                    ]);

                if (!$updated || !$connection) {
                    return;
                }

                IdentityProviderConnection::whereKey($connection->id)->update([
                    'last_auth_failure_at' => now(),
                    'last_auth_failure_code' => $errorCode,
                ]);

                $connection->recordEvent(
                    IdentityProviderConnection::EVENT_ENTRA_LOGIN_FAILED,
                    IdentityProviderConnection::EVENT_OUTCOME_FAILURE,
                    $session->membership()->first(),
                    $errorCode,
                    context: ['mode' => $session->mode],
                );
            }, 3);
        } catch (Throwable $exception) {
            try {
                report($exception);
            } catch (Throwable) {
            }
        }
    }

    /**
     * @param IdentityProviderOidcSession $session
     * @param IdentityProviderConnection $connection
     * @param IdentityProviderAccountData $account
     * @throws Throwable
     * @return string
     */
    protected function prepareSelfLink(
        IdentityProviderOidcSession $session,
        IdentityProviderConnection $connection,
        IdentityProviderAccountData $account,
    ): string {
        return DB::transaction(function () use ($session, $connection, $account) {
            $session = IdentityProviderOidcSession::lockForUpdate()->findOrFail($session->id);
            $connection = IdentityProviderConnection::lockForUpdate()->findOrFail($connection->id);

            if (!$session->isPending() || $session->isExpired() || !$session->browser_token_hash) {
                throw new IdentityProviderException(
                    IdentityProviderErrorCode::ACCOUNT_LINK_INVALID,
                    __('exceptions.identity_providers.account_link_invalid'),
                );
            }

            $this->accessService->assertSsoActive($connection);

            $session->update([
                'verified_account' => [
                    'tenant_id' => $account->tenantId,
                    'object_id' => $account->externalAccountId,
                    'issuer' => $account->issuer,
                    'subject' => $account->subject,
                ],
                'status' => IdentityProviderOidcSession::STATUS_RESOLVED,
                'resolved_at' => now(),
            ]);

            return url_extend_get_params($session->final_url, [
                'entra_exchange' => $session->createExchangeToken(),
                'entra_session' => $session->uid,
            ]);
        }, 3);
    }

    /**
     * @throws OpenIdException
     * @return IdentityProviderOidcSession
     */
    protected function startDashboard(): IdentityProviderOidcSession
    {
        $implementation = Implementation::general();

        return $this->startSession(
            IdentityProviderOidcSession::MODE_DASHBOARD,
            $implementation,
            null,
            $implementation->urlSponsorDashboard('/auth/entra'),
        );
    }

    /**
     * @param Implementation $implementation
     * @param ?string $target
     * @throws OpenIdException
     * @return IdentityProviderOidcSession
     */
    protected function startWebshop(Implementation $implementation, ?string $target): IdentityProviderOidcSession
    {
        $connection = $implementation->organization?->identity_provider_connection;

        if (!$implementation->entraLoginEnabled() || !$connection) {
            throw new IdentityProviderException(
                IdentityProviderErrorCode::WEBSHOP_NOT_ENABLED,
                __('exceptions.identity_providers.webshop_not_enabled'),
            );
        }

        $this->accessService->assertSsoActive($connection);

        return $this->startSession(
            IdentityProviderOidcSession::MODE_WEBSHOP,
            $implementation,
            $connection,
            $implementation->urlWebshop('/auth-link'),
            $target,
        );
    }

    /**
     * @param string $mode
     * @param Implementation $implementation
     * @param ?IdentityProviderConnection $connection
     * @param string $finalUrl
     * @param ?string $target
     * @param ?int $identityId
     * @throws OpenIdException
     * @return IdentityProviderOidcSession
     */
    protected function startSession(
        string $mode,
        Implementation $implementation,
        ?IdentityProviderConnection $connection,
        string $finalUrl,
        ?string $target = null,
        ?int $identityId = null,
    ): IdentityProviderOidcSession {
        $this->accessService->assertModuleEnabled();
        $authorization = $this->adapter->buildAuthorization($connection?->tenant_id);

        return IdentityProviderOidcSession::create([
            'connection_id' => $connection?->id,
            'implementation_id' => $implementation->id,
            'identity_id' => $identityId,
            'mode' => $mode,
            'status' => IdentityProviderOidcSession::STATUS_PENDING,
            'state' => $this->hashToken($authorization->state),
            'nonce' => $authorization->nonce,
            'code_verifier' => $authorization->codeVerifier,
            'authorization_url' => $authorization->redirectUrl,
            'final_url' => $finalUrl,
            'target' => $target,
            'expires_at' => now()->addMinutes((int) Config::get('identity_providers.oidc_session_minutes', 10)),
        ]);
    }

    /**
     * @param IdentityProviderOidcSession $session
     * @param string $tenantId
     * @return IdentityProviderConnection
     */
    protected function resolveConnection(
        IdentityProviderOidcSession $session,
        string $tenantId,
    ): IdentityProviderConnection {
        $connection = $session->connection_id
            ? IdentityProviderConnection::find($session->connection_id)
            : IdentityProviderConnectionQuery::whereCurrentEntraTenant(
                IdentityProviderConnection::query(),
                $tenantId,
            )->first();

        if (!$connection || $connection->tenant_id !== $tenantId) {
            throw new IdentityProviderException(IdentityProviderErrorCode::TENANT_NOT_CONNECTED, 'The Entra tenant is not connected.');
        }

        return $connection;
    }

    /**
     * @param IdentityProviderOidcSession $session
     * @param IdentityProviderConnection $connection
     * @param string $tenantId
     * @param string $objectId
     * @return IdentityProviderMembership
     */
    protected function resolveLoginMembership(
        IdentityProviderOidcSession $session,
        IdentityProviderConnection $connection,
        string $tenantId,
        string $objectId,
    ): IdentityProviderMembership {
        if (!in_array($session->mode, [
            IdentityProviderOidcSession::MODE_DASHBOARD,
            IdentityProviderOidcSession::MODE_WEBSHOP,
        ], true)) {
            throw new IdentityProviderException(IdentityProviderErrorCode::MODE_INVALID, __('exceptions.identity_providers.mode_invalid'));
        }

        $externalIdentity = ExternalIdentityQuery::whereEntraObject(ExternalIdentity::query(), $tenantId, $objectId);
        $membership = $externalIdentity->first()?->memberships()
            ->where('connection_id', $connection->id)
            ->first();

        if (!$membership) {
            throw new IdentityProviderException(IdentityProviderErrorCode::LINK_NOT_FOUND, 'No stable Entra account link was found.');
        }

        return $membership;
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
