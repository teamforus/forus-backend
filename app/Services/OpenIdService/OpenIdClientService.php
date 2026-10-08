<?php

namespace App\Services\OpenIdService;

use Facile\OpenIDClient\Client\ClientBuilder;
use Facile\OpenIDClient\Client\ClientInterface;
use Facile\OpenIDClient\Client\Metadata\ClientMetadata;
use Facile\OpenIDClient\Issuer\IssuerBuilder;
use Facile\OpenIDClient\Service\AuthorizationService;
use Facile\OpenIDClient\Service\Builder\AuthorizationServiceBuilder;
use Facile\OpenIDClient\Session\AuthSession;
use Facile\OpenIDClient\Token\IdTokenVerifierBuilder;
use GuzzleHttp\Psr7\ServerRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Random\RandomException;
use Throwable;

class OpenIdClientService
{
    /**
     * @param array $config
     * @param array $authParams
     * @throws OpenIdException
     * @return array
     */
    public function buildAuthorization(array $config, array $authParams = []): array
    {
        try {
            $context = $this->makeAuthorizationContext($config);

            return [
                'redirect_url' => $this->buildAuthorizationUrl($config, $context, $authParams),
                'state' => $context['state'],
                'nonce' => $context['nonce'],
                'code_verifier' => $context['code_verifier'],
            ];
        } catch (Throwable $exception) {
            throw new OpenIdException('Unable to build OpenID authorization URL.', 0, $exception);
        }
    }

    /**
     * @param array $config
     * @param array $session
     * @param Request $request
     * @param ?ClientInterface $client
     * @throws OpenIdException
     * @return array
     */
    public function resolveCallback(
        array $config,
        array $session,
        Request $request,
        ?ClientInterface $client = null,
    ): array {
        try {
            $config = $this->normalizeConfig($config);
            $state = $request->query('state');

            if (!is_string($state) || !hash_equals((string) $session['state'], $state)) {
                throw new OpenIdException('OpenID callback state mismatch.');
            }

            $authSession = AuthSession::fromArray([
                'state' => $state,
                'nonce' => $session['nonce'],
                'code_verifier' => $session['code_verifier'],
            ]);

            $authorizationService = $this->authorizationService($config);
            $client ??= $this->makeClient($config);

            $tokenSet = $authorizationService->callback(
                $client,
                $authorizationService->getCallbackParams($this->makeServerRequest($request), $client),
                $this->resolveRedirectUrl($config['redirect_url']),
                $authSession,
            );

            $claims = $tokenSet->claims();

            if (($claims['nonce'] ?? null) !== $session['nonce']) {
                throw new OpenIdException('OpenID callback nonce mismatch.');
            }

            return [
                'claims' => $claims,
                'id_token' => $tokenSet->getIdToken(),
                'access_token' => $tokenSet->getAccessToken(),
            ];
        } catch (OpenIdException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new OpenIdException('Unable to resolve OpenID callback.', 0, $exception);
        }
    }

    /**
     * @param array $config
     * @throws OpenIdException
     * @return ClientInterface
     */
    protected function makeClient(array $config): ClientInterface
    {
        $config = $this->normalizeConfig($config);
        $issuer = (new IssuerBuilder())->build($config['issuer']);
        $metadataIssuer = $issuer->getMetadata()->getIssuer();

        if (
            !$config['aad_issuer_validation'] &&
            $this->normalizeIssuerUrl($metadataIssuer) !== $this->normalizeIssuerUrl($config['issuer'])
        ) {
            throw new OpenIdException('OpenID issuer metadata mismatch.');
        }

        $clientMetadata = ClientMetadata::fromArray([
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uris' => [$this->resolveRedirectUrl($config['redirect_url'])],
            'id_token_signed_response_alg' => $config['id_token_signed_response_alg'],
            'token_endpoint_auth_method' => $config['token_endpoint_auth_method'],
        ]);

        return (new ClientBuilder())
            ->setIssuer($issuer)
            ->setClientMetadata($clientMetadata)
            ->build();
    }

    /**
     * @param array $config
     * @param array $values
     * @throws OpenIdException
     * @return array{state: string, nonce: string, code_verifier: string, code_challenge: string}
     */
    protected function makeAuthorizationContext(array $config, array $values = []): array
    {
        try {
            $this->normalizeConfig($config);
            $state = $values['state'] ?? $this->makeRandomToken();
            $nonce = $values['nonce'] ?? $this->makeRandomToken();
            $codeVerifier = $values['code_verifier'] ?? $this->makeCodeVerifier();

            foreach (['state' => $state, 'nonce' => $nonce, 'code_verifier' => $codeVerifier] as $key => $value) {
                if (!is_string($value) || $value === '') {
                    throw new OpenIdException("OpenID authorization context value [$key] is invalid.");
                }
            }

            return [
                'state' => $state,
                'nonce' => $nonce,
                'code_verifier' => $codeVerifier,
                'code_challenge' => $this->makeCodeChallenge($codeVerifier),
            ];
        } catch (Throwable $exception) {
            throw new OpenIdException('Unable to build OpenID authorization context.', 0, $exception);
        }
    }

    /**
     * @param array $config
     * @param array{state: string, nonce: string, code_verifier: string, code_challenge: string} $context
     * @param array $authParams
     * @param ?ClientInterface $client
     * @throws OpenIdException
     * @return string
     */
    protected function buildAuthorizationUrl(
        array $config,
        array $context,
        array $authParams = [],
        ?ClientInterface $client = null,
    ): string {
        try {
            $config = $this->normalizeConfig($config);
            $this->assertAuthorizationContext($context);
            $client ??= $this->makeClient($config);

            return $this->authorizationService($config)->getAuthorizationUri(
                $client,
                array_merge($config['auth_params'], $authParams, [
                    'scope' => implode(' ', $config['scopes']),
                    'state' => $context['state'],
                    'nonce' => $context['nonce'],
                    'code_challenge' => $context['code_challenge'],
                    'code_challenge_method' => $config['code_challenge_method'],
                ]),
            );
        } catch (OpenIdException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new OpenIdException('Unable to build OpenID authorization URL.', 0, $exception);
        }
    }

    /**
     * @param array $config
     * @return AuthorizationService
     */
    protected function authorizationService(array $config): AuthorizationService
    {
        $builder = new AuthorizationServiceBuilder();

        if ($config['aad_issuer_validation'] ?? false) {
            $builder->setIdTokenVerifierBuilder((new IdTokenVerifierBuilder())->setAadIssValidation(true));
        }

        return $builder->build();
    }

    /**
     * @param array{state?: mixed, nonce?: mixed, code_verifier?: mixed, code_challenge?: mixed} $context
     * @throws OpenIdException
     * @return void
     */
    protected function assertAuthorizationContext(array $context): void
    {
        foreach (['state', 'nonce', 'code_verifier', 'code_challenge'] as $key) {
            if (!is_string($context[$key] ?? null) || $context[$key] === '') {
                throw new OpenIdException("OpenID authorization context value [$key] is missing.");
            }
        }
    }

    /**
     * @param array $config
     * @throws OpenIdException
     * @return array
     */
    protected function normalizeConfig(array $config): array
    {
        $required = [
            'issuer', 'client_id', 'client_secret', 'redirect_url', 'scopes',
            'code_challenge_method', 'id_token_signed_response_alg', 'token_endpoint_auth_method',
        ];

        foreach ($required as $key) {
            if (!isset($config[$key]) || $config[$key] === '' || $config[$key] === []) {
                throw new OpenIdException("OpenID configuration value [$key] is missing.");
            }
        }

        $scopes = is_string($config['scopes'])
            ? preg_split('/[\s,]+/', trim($config['scopes']))
            : $config['scopes'];

        if (!is_array($scopes) || array_filter($scopes, fn ($scope) => !is_string($scope) || trim($scope) === '')) {
            throw new OpenIdException('OpenID scopes are invalid.');
        }

        return array_merge([
            'aad_issuer_validation' => false,
        ], $config, [
            'scopes' => array_values($scopes),
            'auth_params' => is_array($config['auth_params'] ?? null) ? $config['auth_params'] : [],
        ]);
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
            $request->server->all(),
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
