<?php

namespace Tests\Traits;

use App\Models\Fund;
use App\Models\Identity;
use App\Models\Implementation;
use App\Services\OpenIdService\Models\OpenIdSession;
use App\Services\OpenIdService\OpenIdException;
use App\Services\OpenIdService\OpenIdService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Testing\TestResponse;

trait MakesOpenIdTestData
{
    use MakesTestOrganizations;

    public const string FAKE_REDIRECT_URL = 'https://openid.example/authorize';
    public const string FAKE_STATE = 'openid-state';
    public const string FAKE_NONCE = 'openid-nonce';
    public const string FAKE_CODE_VERIFIER = 'openid-code-verifier';
    protected const string OPENID_FALLBACK_COOKIE = 'openid_fallback_url';

    /**
     * @param array $overrides
     * @return array
     */
    protected function makeOpenIdContext(array $overrides = []): array
    {
        return [
            'issuer' => 'https://issuer.example',
            'client_id' => 'verid-client',
            'client_secret' => 'verid-secret',
            'redirect_url' => '/api/v1/platform/openid/verid/callback',
            'scopes' => ['openid', 'nin'],
            'bsn_claim' => 'nin.identifier',
            'bsn_claim_source' => 'claims',
            'auth_params' => ['prompt' => 'login'],
            'code_challenge_method' => 'S256',
            'id_token_signed_response_alg' => 'ES384',
            'token_endpoint_auth_method' => 'client_secret_basic',
            'authentication_intent' => [
                'enabled' => false,
                'brand_uuid' => '',
            ],
            ...$overrides,
        ];
    }

    /**
     * @param array $implementationData
     * @param array $organizationData
     * @return Implementation
     */
    protected function makeOpenIdImplementation(
        array $implementationData = [],
        array $organizationData = [],
    ): Implementation {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_openid' => true,
            ...$organizationData,
        ]);

        $implementation = $this->makeTestImplementation($organization, [
            'openid_verid_enabled' => true,
            'openid_verid_context' => $this->makeOpenIdContext(),
            ...$implementationData,
        ]);

        if ($implementationData) {
            $implementation->forceFill($implementationData)->save();
        }

        return $implementation->refresh();
    }

    /**
     * @param array $overrides
     * @return array
     */
    protected function makeOpenIdAuthorization(array $overrides = []): array
    {
        return [
            'redirect_url' => static::FAKE_REDIRECT_URL,
            'state' => token_generator()->generate(40),
            'nonce' => static::FAKE_NONCE,
            'code_verifier' => static::FAKE_CODE_VERIFIER,
            ...$overrides,
        ];
    }

    /**
     * @param Implementation $implementation
     * @param array $data
     * @param Fund|null $fund
     * @param Identity|null $identity
     * @return OpenIdSession
     */
    protected function makeOpenIdSession(
        Implementation $implementation,
        array $data = [],
        ?Fund $fund = null,
        ?Identity $identity = null,
    ): OpenIdSession {
        $sessionRequest = $data['session_request'] ?? (
            $fund ? OpenIdSession::REQUEST_FUND_REQUEST : OpenIdSession::REQUEST_AUTH
        );
        $authorization = $data['authorization'] ?? $this->makeOpenIdAuthorization();
        unset($data['authorization']);

        $session = OpenIdSession::createSession(
            $implementation,
            $data['client_type'] ?? Implementation::FRONTEND_WEBSHOP,
            $data['target'] ?? null,
            $data['provider'] ?? OpenIdService::PROVIDER_VERID,
            $authorization,
            $sessionRequest,
            $fund,
            $data['identity_address'] ?? $identity?->address,
        );

        if ($data) {
            $session->forceFill($data)->save();
        }

        return $session->refresh();
    }

    /**
     * @param string $provider
     * @param array $query
     * @param string|null $fallbackUrl
     * @return TestResponse
     */
    protected function openIdCallbackRequest(
        string $provider = OpenIdService::PROVIDER_VERID,
        array $query = [],
        ?string $fallbackUrl = null,
    ): TestResponse {
        $url = sprintf('/api/v1/platform/openid/%s/callback', $provider);

        if ($query) {
            $url .= '?' . http_build_query($query);
        }

        return ($fallbackUrl ? $this->withUnencryptedCookie(
            self::OPENID_FALLBACK_COOKIE,
            Crypt::encryptString($fallbackUrl),
        ) : $this)->get($url);
    }

    /**
     * @param string|null $callbackBsn
     * @param array|null $callbackPayload
     * @param OpenIdException|null $authorizationException
     * @param OpenIdException|null $callbackException
     * @param array $authorizationData
     * @return void
     */
    protected function fakeOpenIdService(
        ?string $callbackBsn = null,
        ?array $callbackPayload = null,
        ?OpenIdException $authorizationException = null,
        ?OpenIdException $callbackException = null,
        array $authorizationData = [],
    ): void {
        $authorization = $this->makeOpenIdAuthorization([
            'state' => static::FAKE_STATE,
            ...$authorizationData,
        ]);

        $this->app->instance(OpenIdService::class, new class (
            $authorization,
            $callbackPayload,
            $callbackBsn,
            $authorizationException,
            $callbackException,
        ) extends OpenIdService {
            /**
             * @param array $authorization
             * @param array|null $callbackPayload
             * @param string|null $callbackBsn
             * @param OpenIdException|null $authorizationException
             * @param OpenIdException|null $callbackException
             */
            public function __construct(
                private readonly array $authorization,
                private readonly ?array $callbackPayload,
                private readonly ?string $callbackBsn,
                private readonly ?OpenIdException $authorizationException,
                private readonly ?OpenIdException $callbackException,
            ) {
            }

            /**
             * @param Implementation $implementation
             * @param string $provider
             * @throws OpenIdException
             * @return array
             */
            public function buildAuthorizationUrl(Implementation $implementation, string $provider): array
            {
                if ($this->authorizationException) {
                    throw $this->authorizationException;
                }

                return $this->authorization;
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
                if ($this->callbackException) {
                    throw $this->callbackException;
                }

                return $this->callbackPayload ?: [
                    'claims' => [
                        'nin' => [
                            'identifier' => $this->callbackBsn ?: '999994542',
                        ],
                    ],
                    'user_info' => null,
                    'user_info_error' => null,
                    'id_token' => 'openid-id-token',
                    'access_token' => 'openid-access-token',
                ];
            }
        });
    }

    /**
     * @return void
     */
    protected function fakeFailingOpenIdService(): void
    {
        $this->fakeOpenIdService(authorizationException: new OpenIdException('Unable to build authorization URL.'));
    }
}
