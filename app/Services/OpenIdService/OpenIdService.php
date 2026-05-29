<?php

namespace App\Services\OpenIdService;

use App\Models\Identity;
use App\Models\Implementation;
use App\Rules\BsnRule;
use App\Services\OpenIdService\Models\OpenIdSession;
use Facile\OpenIDClient\Client\ClientBuilder;
use Facile\OpenIDClient\Client\ClientInterface;
use Facile\OpenIDClient\Client\Metadata\ClientMetadata;
use Facile\OpenIDClient\Issuer\IssuerBuilder;
use Facile\OpenIDClient\Service\Builder\AuthorizationServiceBuilder;
use Facile\OpenIDClient\Service\Builder\UserInfoServiceBuilder;
use Facile\OpenIDClient\Session\AuthSession;
use GuzzleHttp\Psr7\ServerRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Random\RandomException;
use Throwable;

class OpenIdService
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
            'bsn_claim',
            'bsn_claim_source',
            'code_challenge_method',
            'id_token_signed_response_alg',
            'token_endpoint_auth_method',
        ],
    ];

    protected const string BSN_CLAIM_SOURCE_CLAIMS = 'claims';
    protected const string BSN_CLAIM_SOURCE_USER_INFO = 'user_info';
    protected const array BSN_CLAIM_SOURCES = [
        self::BSN_CLAIM_SOURCE_CLAIMS,
        self::BSN_CLAIM_SOURCE_USER_INFO,
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
            self::PROVIDER_VERID => $implementation->openidVeridAvailable(),
            default => false,
        };
    }

    /**
     * @param string $provider
     * @param array|null $context
     * @return bool
     */
    public static function providerContextConfigured(string $provider, ?array $context): bool
    {
        if (!static::providerConfigured($provider) || !$context) {
            return false;
        }

        foreach (self::PROVIDER_REQUIRED_CONTEXT[$provider] ?? [] as $key) {
            $value = $context[$key] ?? null;

            if (!match ($key) {
                'scopes' => is_array($value) && (bool) array_filter(
                    $value,
                    fn ($scope) => is_string($scope) && trim($scope) !== ''
                ),
                default => is_string($value) && trim($value) !== '',
            }) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $provider
     * @param Implementation $implementation
     * @throws OpenIdException
     * @return array
     */
    public function getProviderConfig(string $provider, Implementation $implementation): array
    {
        if (!static::providerConfigured($provider)) {
            abort(404, 'Unknown OIDC provider');
        }

        $context = match ($provider) {
            self::PROVIDER_VERID => $implementation->openidVeridContext() ?: [],
            default => [],
        };

        if (!static::providerContextConfigured($provider, $context)) {
            throw new OpenIdException('OpenID provider is not configured for this implementation.');
        }

        return $this->normalizeProviderConfig($context);
    }

    /**
     * @param Implementation $implementation
     * @param string $provider
     * @throws OpenIdException
     * @return ClientInterface
     */
    public function makeClient(Implementation $implementation, string $provider): ClientInterface
    {
        if (!static::providerEnabled($provider, $implementation)) {
            throw new OpenIdException('OpenID provider is not enabled for this implementation.');
        }

        $config = $this->getProviderConfig($provider, $implementation);
        $issuer = (new IssuerBuilder())->build($config['issuer']);

        if (
            $this->normalizeIssuerUrl($issuer->getMetadata()->getIssuer()) !==
            $this->normalizeIssuerUrl($config['issuer'])
        ) {
            throw new OpenIdException('OpenID issuer metadata mismatch.');
        }

        $clientMetadata = ClientMetadata::fromArray([
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uris' => [
                $this->resolveRedirectUrl($config['redirect_url']),
            ],
            'id_token_signed_response_alg' => $config['id_token_signed_response_alg'],
            'token_endpoint_auth_method' => $config['token_endpoint_auth_method'],
        ]);

        return (new ClientBuilder())
            ->setIssuer($issuer)
            ->setClientMetadata($clientMetadata)
            ->build();
    }

    /**
     * @param Implementation $implementation
     * @param string $provider
     * @throws OpenIdException
     * @return array
     */
    public function buildAuthorizationUrl(Implementation $implementation, string $provider): array
    {
        try {
            $config = $this->getProviderConfig($provider, $implementation);
            $state = $this->makeRandomToken();
            $nonce = $this->makeRandomToken();
            $codeVerifier = $this->makeCodeVerifier();

            $redirectUrlParms = array_merge(
                $config['auth_params'],
                [
                    'scope' => $this->scopeString($config['scopes']),
                    'state' => $state,
                    'nonce' => $nonce,
                    'code_challenge' => $this->makeCodeChallenge($codeVerifier),
                    'code_challenge_method' => $config['code_challenge_method'],
                ]
            );

            $authorizationService = (new AuthorizationServiceBuilder())->build();
            $redirectUrl = $authorizationService->getAuthorizationUri(
                $this->makeClient($implementation, $provider),
                $redirectUrlParms
            );

            return [
                'redirect_url' => $redirectUrl,
                'state' => $state,
                'nonce' => $nonce,
                'code_verifier' => $codeVerifier,
            ];
        } catch (Throwable $exception) {
            static::logger()->error('OpenID authorization URL build failed.', [
                'provider' => $provider,
                'implementation_id' => $implementation->id,
                ...static::exceptionContext($exception),
            ]);

            throw new OpenIdException('Unable to build OpenID authorization URL.', 0, $exception);
        }
    }

    /**
     * @param string $provider
     * @param OpenIdSession $session
     * @param Request $request
     * @throws OpenIdException
     * @return array
     */
    public function resolveCallback(string $provider, OpenIdSession $session, Request $request): array
    {
        $userInfo = null;
        $userInfoError = null;

        try {
            $implementation = $session->implementation;

            if (!$implementation) {
                throw new OpenIdException('OpenID callback session implementation was not found.');
            }

            $client = $this->makeClient($implementation, $provider);
            $config = $this->getProviderConfig($provider, $implementation);
            $authorizationService = (new AuthorizationServiceBuilder())->build();

            $authSession = AuthSession::fromArray([
                'state' => $session->state,
                'nonce' => $session->nonce,
                'code_verifier' => $session->code_verifier,
            ]);

            $tokenSet = $authorizationService->callback(
                $client,
                $authorizationService->getCallbackParams($this->makeServerRequest($request), $client),
                $this->resolveRedirectUrl($config['redirect_url']),
                $authSession
            );

            $this->assertNonce($tokenSet->claims(), $session);
        } catch (Throwable $exception) {
            static::logger()->error('OpenID callback resolution failed.', [
                'provider' => $provider,
                'session_id' => $session->id,
                'session_uid' => $session->session_uid,
                'session_state' => $session->session_state,
                ...static::exceptionContext($exception),
            ]);

            throw new OpenIdException('Unable to resolve OpenID callback.', 0, $exception);
        }

        try {
            $userInfo = (new UserInfoServiceBuilder())->build()->getUserInfo($client, $tokenSet);
        } catch (Throwable $exception) {
            $userInfoError = static::exceptionContext($exception);

            static::logger()->warning('OpenID userinfo request failed.', [
                'provider' => $provider,
                'session_id' => $session->id,
                ...$userInfoError,
            ]);
        }

        return [
            'claims' => $tokenSet->claims(),
            'user_info' => $userInfo,
            'user_info_error' => $userInfoError,
            'id_token' => $tokenSet->getIdToken(),
            'access_token' => $tokenSet->getAccessToken(),
        ];
    }

    /**
     * @param string $provider
     * @param array $payload
     * @param Implementation $implementation
     * @throws OpenIdException
     * @return string
     */
    public function resolveBsnFromPayload(
        string $provider,
        array $payload,
        Implementation $implementation
    ): string {
        $config = $this->getProviderConfig($provider, $implementation);
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
     * @param string $provider
     * @param string|null $state
     * @throws OpenIdException
     * @return OpenIdSession
     */
    public function resolveCallbackSession(string $provider, ?string $state): OpenIdSession
    {
        if (!static::enabled() || !static::providerConfigured($provider)) {
            $session = $state ? OpenIdSession::query()
                ->where('state', $state)
                ->where('provider', $provider)
                ->first() : null;

            throw OpenIdException::withOpenIdError(
                'not_enabled',
                'OpenID provider is not enabled.',
                null,
                $session,
            );
        }

        if (!$state) {
            throw OpenIdException::withOpenIdError('session_expired', 'OpenID callback state is missing.');
        }

        $session = OpenIdSession::query()
            ->where('state', $state)
            ->where('provider', $provider)
            ->first();

        if (!$session) {
            throw OpenIdException::withOpenIdError('session_expired', 'OpenID callback session was not found.');
        }

        if (!$session->isPending()) {
            throw OpenIdException::withOpenIdError(
                'session_expired',
                'OpenID callback session is not pending.',
                null,
                $session
            );
        }

        if (!$session->implementation?->openidAvailable([$provider])) {
            throw OpenIdException::withOpenIdError(
                'not_enabled',
                'OpenID is not enabled for this implementation.',
                null,
                $session
            );
        }

        if ($session->isExpired()) {
            $session->markExpired();

            throw OpenIdException::withOpenIdError(
                'session_expired',
                'OpenID callback session is expired.',
                null,
                $session
            );
        }

        return $session;
    }

    /**
     * @param OpenIdSession $session
     * @param Request $request
     * @throws OpenIdException
     * @return Identity
     */
    public function resolveBsnAuthIdentity(OpenIdSession $session, Request $request): Identity
    {
        $bsn = $this->resolveCallbackBsn($session, $request);
        $identity = Identity::findByBsn($bsn);

        if (!$identity) {
            if (!$session->implementation->digid_sign_up_allowed) {
                $this->throwMappedSessionError(
                    $session,
                    'uid_not_found',
                    'OpenID BSN identity was not found and signup is disabled.'
                );
            }

            $identity = Identity::build();
        }

        $session->setIdentity($identity);
        $this->assignSessionBsn($session, $bsn);

        return $identity;
    }

    /**
     * @param OpenIdSession $session
     * @param Request $request
     * @throws OpenIdException
     * @return string
     */
    public function resolveBsnFundRequest(OpenIdSession $session, Request $request): string
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
     * @throws OpenIdException
     * @return never
     */
    protected function throwBsnClaimError(string $provider, string $claim, string $source, string $reason): never
    {
        static::logger()->warning('OpenID BSN claim missing or invalid.', [
            'provider' => $provider,
            'bsn_claim' => $claim,
            'bsn_claim_source' => $source,
            'openid_error' => 'missing_claims',
            'reason' => $reason,
        ]);

        throw OpenIdException::withOpenIdError('missing_claims', 'OpenID BSN claim missing or invalid.');
    }

    /**
     * @param OpenIdSession $session
     * @param Request $request
     * @throws OpenIdException
     * @return string
     */
    protected function resolveCallbackBsn(OpenIdSession $session, Request $request): string
    {
        try {
            return $this->resolveBsnFromPayload(
                $session->provider,
                $this->resolveCallback($session->provider, $session, $request),
                $session->implementation
            );
        } catch (OpenIdException $exception) {
            $openIdError = $exception->getOpenIdError() ?: 'callback_failed';

            static::logger()->warning('OpenID BSN callback mapped to error response.', [
                'provider' => $session->provider,
                'session_id' => $session->id,
                'session_uid' => $session->session_uid,
                'session_request' => $session->session_request,
                'openid_error' => $openIdError,
                ...static::exceptionContext($exception),
            ]);

            throw OpenIdException::withOpenIdError(
                $openIdError,
                'OpenID BSN callback mapped to error response.',
                $exception,
                $session
            );
        }
    }

    /**
     * @param OpenIdSession $session
     * @param string $bsn
     * @throws OpenIdException
     * @return bool
     */
    protected function assignSessionBsn(OpenIdSession $session, string $bsn): bool
    {
        $sessionIdentity = $session->sessionIdentity();

        if (!$sessionIdentity) {
            $this->throwMappedSessionError(
                $session,
                'session_expired',
                'OpenID callback session identity was not found.'
            );
        }

        $sessionIdentityBsn = $sessionIdentity->bsn;
        $bsnIdentity = Identity::findByBsn($bsn);
        $organization = $session->sessionOrganization();

        if ($sessionIdentityBsn && $sessionIdentityBsn !== $bsn) {
            $this->throwMappedSessionError(
                $session,
                'uid_dont_match',
                'OpenID BSN differs from the session identity BSN.'
            );
        }

        if ($bsnIdentity && $bsnIdentity->address !== $sessionIdentity->address) {
            $this->throwMappedSessionError(
                $session,
                'uid_used',
                'OpenID BSN belongs to another identity.'
            );
        }

        if (!$organization) {
            $this->throwMappedSessionError(
                $session,
                'callback_failed',
                'OpenID callback session organization was not found.'
            );
        }

        if ($organization->bsn_enabled) {
            return (bool) $sessionIdentity->setBsnRecord($bsn);
        }

        return false;
    }

    /**
     * @param OpenIdSession $session
     * @param string $openIdError
     * @param string $message
     * @param Throwable|null $previous
     * @throws OpenIdException
     * @return never
     */
    protected function throwMappedSessionError(
        OpenIdSession $session,
        string $openIdError,
        string $message,
        ?Throwable $previous = null
    ): never {
        static::logger()->warning('OpenID BSN callback mapped to error response.', [
            'provider' => $session->provider,
            'session_id' => $session->id,
            'session_uid' => $session->session_uid,
            'session_request' => $session->session_request,
            'openid_error' => $openIdError,
            ...($previous ? static::exceptionContext($previous) : []),
        ]);

        throw OpenIdException::withOpenIdError($openIdError, $message, $previous, $session);
    }

    /**
     * @param array $scopes
     * @return string
     */
    protected function scopeString(array $scopes): string
    {
        return implode(' ', $scopes);
    }

    /**
     * @param array $claims
     * @param OpenIdSession $session
     * @throws OpenIdException
     * @return void
     */
    protected function assertNonce(array $claims, OpenIdSession $session): void
    {
        if (($claims['nonce'] ?? null) !== $session->nonce) {
            throw new OpenIdException('OpenID callback nonce mismatch.');
        }
    }

    /**
     * @param string $redirectUrl
     * @return string
     */
    protected function resolveRedirectUrl(string $redirectUrl): string
    {
        return Str::startsWith($redirectUrl, 'http') ? $redirectUrl : url($redirectUrl);
    }

    /**
     * @param string $issuerUrl
     * @return string
     */
    protected function normalizeIssuerUrl(string $issuerUrl): string
    {
        $parts = parse_url($issuerUrl);

        if (!$parts || !isset($parts['scheme'], $parts['host'])) {
            return rtrim($issuerUrl, '/');
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower($parts['host']);
        $path = isset($parts['path']) ? rtrim($parts['path'], '/') : '';
        $port = $parts['port'] ?? null;
        $defaultPort = ($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443);

        return sprintf('%s://%s%s%s', $scheme, $host, $port && !$defaultPort ? ":$port" : '', $path);
    }

    /**
     * @param Request $request
     * @return ServerRequest
     */
    protected function makeServerRequest(Request $request): ServerRequest
    {
        return new ServerRequest(
            $request->method(),
            $request->fullUrl(),
            $request->headers->all(),
            $request->isMethod('POST') ? http_build_query($request->request->all()) : null,
            '1.1',
            $request->server->all()
        );
    }

    /**
     * @throws RandomException
     * @return string
     */
    protected function makeRandomToken(): string
    {
        return $this->base64UrlEncode(random_bytes(32));
    }

    /**
     * @throws RandomException
     * @return string
     */
    protected function makeCodeVerifier(): string
    {
        return $this->base64UrlEncode(random_bytes(64));
    }

    /**
     * @param string $codeVerifier
     * @return string
     */
    protected function makeCodeChallenge(string $codeVerifier): string
    {
        return $this->base64UrlEncode(hash('sha256', $codeVerifier, true));
    }

    /**
     * @param string $value
     * @return string
     */
    protected function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
