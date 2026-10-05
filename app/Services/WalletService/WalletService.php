<?php

namespace App\Services\WalletService;

use App\Models\Identity;
use App\Models\Implementation;
use App\Rules\BsnRule;
use App\Services\OpenIdService\OpenIdClientService;
use App\Services\OpenIdService\OpenIdException;
use App\Services\WalletService\Exceptions\WalletWorkflowException;
use App\Services\WalletService\Models\WalletFlow;
use App\Services\WalletService\Models\WalletSession;
use App\Services\WalletService\VerId\VerIdDisclosureToken;
use App\Services\WalletService\VerId\VerIdIntent;
use App\Services\WalletService\VerId\VerIdIntentCreationException;
use Facile\OpenIDClient\Client\ClientInterface;
use Facile\OpenIDClient\Exception\OAuth2Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Throwable;

class WalletService
{
    public const string PROVIDER_VERID = 'verid';

    public const array PROVIDERS = [
        self::PROVIDER_VERID,
    ];

    protected const array PROVIDER_REQUIRED_CONTEXT = [
        self::PROVIDER_VERID => [
            'issuer',
            'client_id',
            'client_secret',
            'redirect_url',
            'scopes',
            'code_challenge_method',
            'id_token_signed_response_alg',
            'token_endpoint_auth_method',
        ],
    ];

    protected const string BSN_CLAIM_SOURCE_CLAIMS = 'claims';

    protected const array BSN_CLAIM_SOURCES = [
        self::BSN_CLAIM_SOURCE_CLAIMS,
    ];

    /**
     * @return LoggerInterface
     */
    public static function logger(): LoggerInterface
    {
        return Log::channel(Config::get('openid.log_channel', 'openid'));
    }

    /**
     * @return bool
     */
    public static function enabled(): bool
    {
        return (bool) Config::get('openid.enabled');
    }

    /**
     * @param Implementation|null $implementation
     * @return bool
     */
    public static function disclosureAvailable(?Implementation $implementation): bool
    {
        return App::environment('local') && static::enabled() && $implementation && !$implementation->isGeneral() &&
            $implementation->organization?->allow_openid && $implementation->openid_enabled &&
            $implementation->availableWalletFlowsForProvider(self::PROVIDER_VERID, WalletFlow::TYPE_DISCLOSURE)
                ->isNotEmpty();
    }

    /**
     * @param WalletSession $session
     * @param Request $request
     * @throws WalletWorkflowException
     * @return array
     */
    public function resolveDisclosure(WalletSession $session, Request $request): array
    {
        try {
            $flow = $this->resolveSessionFlow($session);
            $this->assertFlowEnabled($session->implementation, $flow);

            $result = $this->resolveDisclosureCallback(
                $this->getProviderConfig($flow),
                $session->only(['state', 'nonce', 'code_verifier']),
                $request,
                $session->meta['challenge'] ?? '',
            );

            if (App::environment('local') && Config::get('openid.log_raw_response', false)) {
                static::logger()->info('Ver.id disclosure verified.', [
                    'session_uid' => $session->session_uid,
                    'flow_id' => $flow->id,
                    'header' => $result['header'],
                    'payload' => $result['payload'],
                    'raw_response' => $result,
                ]);
            }

            return $result['payload'];
        } catch (Throwable $exception) {
            $providerException = $exception->getPrevious();

            static::logger()->warning('Ver.id disclosure failed.', [
                'session_uid' => $session->session_uid,
                'exception_class' => get_class($exception),
                'message' => $exception->getMessage(),
                ...($providerException instanceof OAuth2Exception ? [
                    'oauth_error' => $providerException->getError(),
                    'oauth_error_description' => $providerException->getDescription(),
                ] : []),
            ]);

            throw WalletWorkflowException::withError(
                isset($session->meta['fund_id']) && $providerException instanceof OAuth2Exception &&
                $providerException->getError() === 'access_denied'
                    ? WalletWorkflowException::ERROR_DISCLOSURE_CANCELLED
                    : WalletWorkflowException::ERROR_CALLBACK_FAILED,
                'Unable to complete disclosure.',
                $exception,
                $session,
            );
        }
    }

    /**
     * @param string $provider
     * @return bool
     */
    public static function providerConfigured(string $provider): bool
    {
        return in_array($provider, self::PROVIDERS, true);
    }

    /**
     * @param Implementation|null $implementation
     * @return array
     */
    public static function enabledProviderKeys(?Implementation $implementation = null): array
    {
        if (!static::enabled()) {
            return [];
        }

        return array_values(array_filter(
            self::PROVIDERS,
            fn ($provider) => static::providerEnabled($provider, $implementation)
        ));
    }

    /**
     * @param string $provider
     * @param Implementation|null $implementation
     * @return bool
     */
    public static function providerEnabled(string $provider, ?Implementation $implementation = null): bool
    {
        if (!static::enabled() || !static::providerConfigured($provider)) {
            return false;
        }

        return !$implementation || match ($provider) {
            self::PROVIDER_VERID => $implementation->veridWalletAvailable(),
            default => false,
        };
    }

    /**
     * @param string $provider
     * @param array|null $context
     * @param string $type
     * @return bool
     */
    public static function providerContextConfigured(
        string $provider,
        ?array $context,
        string $type = WalletFlow::TYPE_AUTHENTICATION,
    ): bool {
        if (!static::providerConfigured($provider) || !$context) {
            return false;
        }

        $required = self::PROVIDER_REQUIRED_CONTEXT[$provider];

        if ($type === WalletFlow::TYPE_AUTHENTICATION) {
            $required = [...$required, 'bsn_claim', 'bsn_claim_source'];
        } elseif ($type !== WalletFlow::TYPE_DISCLOSURE) {
            return false;
        }

        foreach ($required as $key) {
            $value = $context[$key] ?? null;

            if (!match ($key) {
                'bsn_claim_source' => is_string($value) &&
                    in_array(strtolower(trim($value)), self::BSN_CLAIM_SOURCES, true),
                'scopes' => is_array($value) && array_filter(
                    $value,
                    fn ($scope) => is_string($scope) && trim($scope) !== ''
                ),
                default => is_string($value) && trim($value) !== '',
            }) {
                return false;
            }
        }

        return in_array($type === WalletFlow::TYPE_DISCLOSURE ? 'disclosure' : 'openid', $context['scopes'], true);
    }

    /**
     * @param WalletFlow $flow
     * @throws WalletWorkflowException
     * @return array
     */
    public function getProviderConfig(WalletFlow $flow): array
    {
        $provider = $flow->provider;

        if (!static::providerConfigured($provider)) {
            abort(404, 'Unknown OIDC provider');
        }

        $context = $flow->providerContext() ?: [];

        if (!$flow->configured()) {
            throw new WalletWorkflowException(trans('wallets.exceptions.flow_not_configured'));
        }

        return $this->normalizeProviderConfig($context);
    }

    /**
     * @param Implementation $implementation
     * @param WalletFlow $flow
     * @throws WalletWorkflowException
     * @return array
     */
    public function buildAuthorizationUrl(Implementation $implementation, WalletFlow $flow): array
    {
        try {
            $this->assertFlowEnabled($implementation, $flow);
            $config = $this->getProviderConfig($flow);
            $challenge = $flow->type === WalletFlow::TYPE_DISCLOSURE ? (string) Str::uuid() : null;

            $authorization = resolve(OpenIdClientService::class)->buildAuthorizationWithContext(
                $config,
                fn (ClientInterface $client, string $codeChallenge) => $this->resolveIntentMeta(
                    $flow->provider,
                    $implementation,
                    $config,
                    $client,
                    $codeChallenge,
                    $challenge,
                ),
            );

            return [
                ...$authorization,
                'meta' => [
                    ...$authorization['auth_params'],
                    ...($challenge !== null ? ['challenge' => $challenge] : []),
                ],
            ];
        } catch (Throwable $exception) {
            static::logger()->error('Wallet authorization URL build failed.', [
                'provider' => $flow->provider,
                'flow_key' => $flow->key,
                'implementation_id' => $implementation->id,
                ...static::exceptionContext($exception),
            ]);

            throw new WalletWorkflowException('Unable to build Wallet authorization URL.', 0, $exception);
        }
    }

    /**
     * @param string $provider
     * @param WalletSession $session
     * @param Request $request
     * @throws WalletWorkflowException
     * @return array
     */
    public function resolveCallback(string $provider, WalletSession $session, Request $request): array
    {
        try {
            $implementation = $session->implementation;

            if (!$implementation) {
                throw new WalletWorkflowException('Wallet callback session implementation was not found.');
            }

            $flow = $this->resolveSessionFlow($session);
            $this->assertFlowEnabled($implementation, $flow);

            return resolve(OpenIdClientService::class)->resolveCallback(
                $this->getProviderConfig($flow),
                $session->only(['state', 'nonce', 'code_verifier']),
                $request,
            );
        } catch (Throwable $exception) {
            static::logger()->error('Wallet callback resolution failed.', [
                'provider' => $provider,
                'session_id' => $session->id,
                'session_uid' => $session->session_uid,
                'session_state' => $session->session_state,
                ...static::exceptionContext($exception),
            ]);

            throw new WalletWorkflowException('Unable to resolve Wallet callback.', 0, $exception);
        }
    }

    /**
     * @param array $payload
     * @param WalletFlow $flow
     * @throws WalletWorkflowException
     * @return string
     */
    public function resolveBsnFromPayload(
        array $payload,
        WalletFlow $flow
    ): string {
        $provider = $flow->provider;
        $config = $this->getProviderConfig($flow);
        $claim = trim((string) $config['bsn_claim']);
        $source = strtolower(trim((string) $config['bsn_claim_source']));

        if (!$claim) {
            $this->throwBsnClaimError($provider, $claim, $source, 'missing_config_claim');
        }

        if (!in_array($source, self::BSN_CLAIM_SOURCES, true)) {
            $this->throwBsnClaimError($provider, $claim, $source, 'unsupported_claim_source');
        }

        $sourcePayload = $payload[$source] ?? null;

        if (!is_array($sourcePayload)) {
            $this->throwBsnClaimError($provider, $claim, $source, 'missing_claim_source');
        }

        $claimValue = data_get($sourcePayload, $claim);

        if (!is_string($claimValue)) {
            $this->throwBsnClaimError($provider, $claim, $source, 'invalid_claim_type');
        }

        $bsn = $this->normalizeBsnClaim($claimValue);

        if (!$bsn) {
            $this->throwBsnClaimError($provider, $claim, $source, 'invalid_claim_value');
        }

        return $bsn;
    }

    /**
     * @param WalletSession $session
     * @return WalletFlow|null
     */
    public function findSessionFlow(WalletSession $session): ?WalletFlow
    {
        if (!$session->wallet_flow) {
            return null;
        }

        $type = $session->session_request === WalletSession::REQUEST_DISCLOSURE
            ? WalletFlow::TYPE_DISCLOSURE
            : WalletFlow::TYPE_AUTHENTICATION;

        return $session->implementation?->availableWalletFlows($type)->firstWhere('id', $session->wallet_flow->id);
    }

    /**
     * @param WalletSession $session
     * @throws WalletWorkflowException
     * @return WalletFlow
     */
    public function resolveSessionFlow(WalletSession $session): WalletFlow
    {
        return $this->findSessionFlow($session) ?: throw WalletWorkflowException::withError(
            WalletWorkflowException::ERROR_NOT_ENABLED,
            trans('wallets.exceptions.flow_not_enabled'),
            null,
            $session
        );
    }

    /**
     * @param string $provider
     * @param string|null $state
     * @throws WalletWorkflowException
     * @return WalletSession
     */
    public function resolveCallbackSession(string $provider, ?string $state): WalletSession
    {
        $session = $state ? WalletSession::query()
            ->where('state', $state)
            ->whereRelation('wallet_flow', 'provider', $provider)
            ->first() : null;

        if (!static::enabled() || !static::providerConfigured($provider)) {
            throw WalletWorkflowException::withError(
                WalletWorkflowException::ERROR_NOT_ENABLED,
                'Wallet provider is not enabled.',
                null,
                $session,
            );
        }

        if (!$state) {
            throw WalletWorkflowException::withError(
                WalletWorkflowException::ERROR_SESSION_EXPIRED,
                'Wallet callback state is missing.'
            );
        }

        if (!$session) {
            throw WalletWorkflowException::withError(
                WalletWorkflowException::ERROR_SESSION_EXPIRED,
                'Wallet callback session was not found.'
            );
        }

        if (!$session->isPending()) {
            throw WalletWorkflowException::withError(
                WalletWorkflowException::ERROR_SESSION_EXPIRED,
                'Wallet callback session is not pending.',
                null,
                $session
            );
        }

        $flow = $this->resolveSessionFlow($session);

        $available = $session->session_request === WalletSession::REQUEST_DISCLOSURE
            ? static::disclosureAvailable($session->implementation)
            : $session->implementation?->walletAvailable([$flow->provider]);

        if (!$available) {
            throw WalletWorkflowException::withError(
                WalletWorkflowException::ERROR_NOT_ENABLED,
                'Wallet is not enabled for this implementation.',
                null,
                $session
            );
        }

        if ($session->isExpired()) {
            $session->markExpired();

            throw WalletWorkflowException::withError(
                WalletWorkflowException::ERROR_SESSION_EXPIRED,
                'Wallet callback session is expired.',
                null,
                $session
            );
        }

        return $session;
    }

    /**
     * @param WalletSession $session
     * @param Request $request
     * @throws WalletWorkflowException
     * @return Identity
     */
    public function resolveBsnAuthIdentity(WalletSession $session, Request $request): Identity
    {
        $bsn = $this->resolveCallbackBsn($session, $request);
        $identity = Identity::findByBsn($bsn);

        if (!$identity) {
            if (!$session->implementation->digid_sign_up_allowed) {
                $this->throwMappedSessionError(
                    $session,
                    WalletWorkflowException::ERROR_UID_NOT_FOUND,
                    'Wallet BSN identity was not found and signup is disabled.'
                );
            }

            $identity = Identity::build();
        }

        $session->setIdentity($identity);
        $this->assignSessionBsn($session, $bsn);

        return $identity;
    }

    /**
     * @param WalletSession $session
     * @param Request $request
     * @throws WalletWorkflowException
     * @return string
     */
    public function resolveBsnFundRequest(WalletSession $session, Request $request): string
    {
        return $this->assignSessionBsn(
            $session,
            $this->resolveCallbackBsn($session, $request)
        ) ? 'signed_up' : 'signed_in';
    }

    /**
     * @param Throwable $exception
     * @return array
     */
    public static function exceptionContext(Throwable $exception): array
    {
        $previous = $exception->getPrevious();
        $logExceptionMessages = Config::get('openid.log_exception_messages', false);

        return [
            'exception_class' => get_class($exception),
            ...($logExceptionMessages ? [
                'exception_message' => $exception->getMessage(),
            ] : []),
            ...($previous ? [
                'previous_exception_class' => get_class($previous),
                ...($logExceptionMessages ? [
                    'previous_exception_message' => $previous->getMessage(),
                ] : []),
            ] : []),
        ];
    }

    /**
     * @param array $config
     * @param array $session
     * @param Request $request
     * @param string $challenge
     * @throws OpenIdException
     * @return array
     */
    protected function resolveDisclosureCallback(
        array $config,
        array $session,
        Request $request,
        string $challenge,
    ): array {
        try {
            $service = resolve(OpenIdClientService::class);
            $client = $service->makeClient($config);
            $tokenSet = $service->exchangeCallback($config, $session, $request, $client, requireCode: true);

            return (new VerIdDisclosureToken())->verify($tokenSet->getAccessToken() ?? '', $client, $challenge);
        } catch (OpenIdException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new OpenIdException('Unable to resolve disclosure callback.', 0, $exception);
        }
    }

    /**
     * @param Implementation $implementation
     * @param WalletFlow $flow
     * @throws WalletWorkflowException
     * @return void
     */
    protected function assertFlowEnabled(Implementation $implementation, WalletFlow $flow): void
    {
        $enabled = $flow->type === WalletFlow::TYPE_AUTHENTICATION
            ? static::providerEnabled($flow->provider, $implementation)
            : static::disclosureAvailable($implementation);

        if (!$enabled ||
            !$implementation->availableWalletFlows($flow->type)->firstWhere('id', $flow->id)) {
            throw new WalletWorkflowException(trans('wallets.exceptions.flow_not_enabled'));
        }
    }

    /**
     * @param array $config
     * @return array
     */
    protected function normalizeProviderConfig(array $config): array
    {
        $scopes = $config['scopes'] ?? [];

        if (is_string($scopes)) {
            $scopes = preg_split('/[\s,]+/', trim($scopes)) ?: [];
        }

        if (!is_array($scopes)) {
            $scopes = [];
        }

        $config['scopes'] = array_values(array_filter(
            $scopes,
            fn ($scope) => is_string($scope) && trim($scope) !== ''
        ));

        if (!is_array($config['auth_params'] ?? null)) {
            $config['auth_params'] = [];
        }

        return $config;
    }

    /**
     * @param string $provider
     * @param Implementation $implementation
     * @param array $config
     * @param ClientInterface $client
     * @param string $codeChallenge
     * @param string|null $challenge
     * @throws WalletWorkflowException
     * @return array
     */
    protected function resolveIntentMeta(
        string $provider,
        Implementation $implementation,
        array $config,
        ClientInterface $client,
        string $codeChallenge,
        ?string $challenge = null,
    ): array {
        $brandUuid = $implementation->veridBrandUuid();

        if ($provider !== self::PROVIDER_VERID || (!$brandUuid && $challenge === null)) {
            return [];
        }

        $intent = new VerIdIntent(
            $config,
            $codeChallenge,
            $client->getIssuer()->getMetadata()->get('intent_endpoint'),
            $brandUuid ?? '',
            $challenge !== null ? 'disclosure' : 'openid',
            $challenge,
        );

        $response = $intent->send();

        if ($response->successful()) {
            return [
                'intent_id' => $response->id(),
            ];
        }

        static::logger()->warning('Ver.id intent creation failed.', [
            'provider' => $provider,
            'implementation_id' => $implementation->id,
            'brand_uuid' => $intent->brandUuid(),
            'intent_endpoint' => $intent->endpoint(),
            ...$response->logContext(
                (bool) Config::get('openid.log_raw_response', false),
                (bool) Config::get('openid.log_exception_messages', false),
            ),
        ]);

        throw new VerIdIntentCreationException($response->exception());
    }

    /**
     * @param string $value
     * @return string|null
     */
    protected function normalizeBsnClaim(string $value): ?string
    {
        $normalized = trim($value);

        if (!preg_match('/^[0-9]{8,9}$/', $normalized)) {
            return null;
        }

        $normalized = str_pad($normalized, 9, '0', STR_PAD_LEFT);

        return (new BsnRule())->passes('bsn', $normalized) ? $normalized : null;
    }

    /**
     * @param string $provider
     * @param string $claim
     * @param string $source
     * @param string $reason
     * @throws WalletWorkflowException
     * @return never
     */
    protected function throwBsnClaimError(string $provider, string $claim, string $source, string $reason): never
    {
        static::logger()->warning('Wallet BSN claim missing or invalid.', [
            'provider' => $provider,
            'bsn_claim' => $claim,
            'bsn_claim_source' => $source,
            'wallet_error' => WalletWorkflowException::ERROR_MISSING_CLAIMS,
            'reason' => $reason,
        ]);

        throw WalletWorkflowException::withError(
            WalletWorkflowException::ERROR_MISSING_CLAIMS,
            'Wallet BSN claim missing or invalid.'
        );
    }

    /**
     * @param WalletSession $session
     * @param Request $request
     * @throws WalletWorkflowException
     * @return string
     */
    protected function resolveCallbackBsn(WalletSession $session, Request $request): string
    {
        $provider = $session->wallet_flow?->provider;

        try {
            $flow = $this->resolveSessionFlow($session);
            $provider = $flow->provider;

            return $this->resolveBsnFromPayload(
                $this->resolveCallback($provider, $session, $request),
                $flow
            );
        } catch (WalletWorkflowException $exception) {
            $errorCode = $exception->errorCode() ?: WalletWorkflowException::ERROR_CALLBACK_FAILED;

            static::logger()->warning('Wallet BSN callback mapped to error response.', [
                'provider' => $provider,
                'session_id' => $session->id,
                'session_uid' => $session->session_uid,
                'session_request' => $session->session_request,
                'wallet_error' => $errorCode,
                ...static::exceptionContext($exception),
            ]);

            throw WalletWorkflowException::withError(
                $errorCode,
                'Wallet BSN callback mapped to error response.',
                $exception,
                $session
            );
        }
    }

    /**
     * @param WalletSession $session
     * @param string $bsn
     * @throws WalletWorkflowException
     * @return bool
     */
    protected function assignSessionBsn(WalletSession $session, string $bsn): bool
    {
        $sessionIdentity = $session->sessionIdentity();

        if (!$sessionIdentity) {
            $this->throwMappedSessionError(
                $session,
                WalletWorkflowException::ERROR_SESSION_EXPIRED,
                'Wallet callback session identity was not found.'
            );
        }

        $sessionIdentityBsn = $sessionIdentity->bsn;
        $bsnIdentity = Identity::findByBsn($bsn);
        $organization = $session->sessionOrganization();

        if ($sessionIdentityBsn && $sessionIdentityBsn !== $bsn) {
            $this->throwMappedSessionError(
                $session,
                WalletWorkflowException::ERROR_UID_DONT_MATCH,
                'Wallet BSN differs from the session identity BSN.'
            );
        }

        if ($bsnIdentity && $bsnIdentity->address !== $sessionIdentity->address) {
            $this->throwMappedSessionError(
                $session,
                WalletWorkflowException::ERROR_UID_USED,
                'Wallet BSN belongs to another identity.'
            );
        }

        if (!$organization) {
            $this->throwMappedSessionError(
                $session,
                WalletWorkflowException::ERROR_CALLBACK_FAILED,
                'Wallet callback session organization was not found.'
            );
        }

        if ($organization->bsn_enabled) {
            return (bool) $sessionIdentity->setBsnRecord($bsn);
        }

        return false;
    }

    /**
     * @param WalletSession $session
     * @param string $errorCode
     * @param string $message
     * @param Throwable|null $previous
     * @throws WalletWorkflowException
     * @return never
     */
    protected function throwMappedSessionError(
        WalletSession $session,
        string $errorCode,
        string $message,
        ?Throwable $previous = null
    ): never {
        static::logger()->warning('Wallet BSN callback mapped to error response.', [
            'provider' => $session->wallet_flow?->provider,
            'session_id' => $session->id,
            'session_uid' => $session->session_uid,
            'session_request' => $session->session_request,
            'wallet_error' => $errorCode,
            ...($previous ? static::exceptionContext($previous) : []),
        ]);

        throw WalletWorkflowException::withError($errorCode, $message, $previous, $session);
    }
}
