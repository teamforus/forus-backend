<?php

namespace App\Services\DigIdService\Repositories;

use App\Services\DigIdService\DigIdException;
use App\Services\DigIdService\Objects\ClientTls;
use App\Services\DigIdService\Objects\DigidAuthRequestData;
use App\Services\DigIdService\Objects\DigidAuthResolveData;
use App\Services\DigIdService\Repositories\Interfaces\DigIdRepo;
use App\Services\DigIdService\TmpFile;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class DigIdCgiRepo extends DigIdRepo
{
    public const string DIGID_SUCCESS = '0000';
    public const string DIGID_UNAVAILABLE = '0001';
    public const string DIGID_TEMPORARY_UNAVAILABLE_1 = '0003';
    public const string DIGID_VERIFICATION_FAILED_1 = '0004';
    public const string DIGID_VERIFICATION_FAILED_2 = '0007';
    public const string DIGID_ILLEGAL_REQUEST = '0030';
    public const string DIGID_ERROR_APP_ID = '0032';
    public const string DIGID_ERROR_ASELECT = '0033';
    public const string DIGID_CANCELLED = '0040';
    public const string DIGID_BUSY = '0050';
    public const string DIGID_INVALID_SESSION = '0070';
    public const string DIGID_WEBSERVICE_NOT_ACTIVE = '0080';
    public const string DIGID_WEBSERVICE_NOT_AUTHORISED = '0099';
    public const string DIGID_TEMPORARY_UNAVAILABLE_2 = '010c';
    public const string DIGID_API_NOT_RESPONDING = 'API_0000';

    public const string URL_API_SANDBOX = 'https://was-preprod1.digid.nl/was/server';
    public const string URL_API_PRODUCTION = 'https://was.digid.nl/was/server';

    public const string ENV_SANDBOX = 'sandbox';
    public const string ENV_PRODUCTION = 'production';

    public const string DIGID_CERT_DISABLE = 'disable';

    protected ?string $app_id = null;
    protected ?string $shared_secret = null;
    protected ?string $a_select_server = null;
    protected string $environment = self::ENV_SANDBOX;
    protected ?string $trusted_certificate = null;

    /**
     * DigIdRepo constructor.
     * @param string $env
     * @throws DigIdException
     */
    public function __construct(string $env = self::ENV_SANDBOX)
    {
        if (!in_array($env, [self::ENV_SANDBOX, self::ENV_PRODUCTION])) {
            throw $this->makeException('Invalid environment.');
        }

        $this->environment = $env;
    }

    /**
     * @param mixed $errorCode
     * @return string
     */
    public static function responseCodeDetails(mixed $errorCode): string
    {
        return match((string) $errorCode) {
            default => (string) $errorCode,
            self::DIGID_SUCCESS => 'digid_success',
            self::DIGID_UNAVAILABLE => 'digid_unavailable',
            self::DIGID_TEMPORARY_UNAVAILABLE_1 => 'digid_temporary_unavailable_1',
            self::DIGID_TEMPORARY_UNAVAILABLE_2 => 'digid_temporary_unavailable_2',
            self::DIGID_VERIFICATION_FAILED_1 => 'digid_verification_failed_1',
            self::DIGID_VERIFICATION_FAILED_2 => 'digid_verification_failed_2',
            self::DIGID_ILLEGAL_REQUEST => 'digid_illegal_request',
            self::DIGID_ERROR_APP_ID => 'digid_error_app_id',
            self::DIGID_ERROR_ASELECT => 'digid_error_aselect',
            self::DIGID_CANCELLED => 'digid_cancelled',
            self::DIGID_BUSY => 'digid_busy',
            self::DIGID_INVALID_SESSION => 'digid_invalid_session',
            self::DIGID_WEBSERVICE_NOT_ACTIVE => 'digid_webservice_not_active',
            self::DIGID_WEBSERVICE_NOT_AUTHORISED => 'digid_webservice_not_authorised',
            self::DIGID_API_NOT_RESPONDING => 'digid_api_not_responding',
        };
    }

    /**
     * @param string $redirectUrl
     * @param string $sessionSecret
     * @param ClientTls|null $tlsCert
     * @throws DigIdException
     * @return DigidAuthRequestData
     */
    public function makeAuthRequest(
        string $redirectUrl,
        string $sessionSecret,
        ?ClientTls $tlsCert = null,
    ): DigidAuthRequestData {
        $redirectUrl = url_extend_get_params($redirectUrl, [
            'session_secret' => $sessionSecret,
        ]);

        $request = $this->makeRequestUrl($this->makeAuthorizedRequest(array_merge([
            'request' => 'authenticate',
            'app_url' => $redirectUrl,
        ])));

        $response = $this->makeCall($request, 'get', $tlsCert);
        $result = $this->parseResponseBody($response->getBody());
        $result_code = $result['result_code'] ?? false;

        if ($result_code !== self::DIGID_SUCCESS) {
            throw $this->makeException('Digid API error code received.', $result_code);
        }

        if (!$this->validateAuthRequestResponse($result)) {
            throw $this->makeException('Digid invalid auth request, response body.', $result_code);
        }

        $authRedirectParams = Arr::only($result, ['rid', 'a-select-server']);
        $authRedirectUrl = url_extend_get_params($result['as_url'], $authRedirectParams);

        return (new DigidAuthRequestData())
            ->setMeta($result)
            ->setRequestId($result['rid'])
            ->setAuthResolveUrl($redirectUrl)
            ->setAuthRedirectUrl($authRedirectUrl);
    }

    /**
     * @param Request $request
     * @param string $requestId
     * @param string $sessionSecret
     * @param ClientTls|null $tlsCert
     * @throws DigIdException
     * @return DigidAuthResolveData
     */
    public function resolveResponse(
        Request $request,
        string $requestId,
        string $sessionSecret,
        ?ClientTls $tlsCert = null,
    ): DigidAuthResolveData {
        $resolveParams = [
            'request' => 'verify_credentials',
            'rid' => $request->get('rid', ''),
            'a-select-server' => $request->get('a-select-server', ''),
            'aselect_credentials' => $request->get('aselect_credentials', ''),
        ];

        if ($sessionSecret !== $request->get('session_secret')) {
            throw $this->makeException('DigiD: invalid response.', 'unknown_error');
        }

        if ($resolveParams['rid'] !== $requestId) {
            throw $this->makeException('DigiD: invalid response.', 'unknown_error');
        }

        $request = $this->makeRequestUrl($this->makeAuthorizedRequest($resolveParams));
        $response = $this->makeCall($request, 'get', $tlsCert);
        $result = $this->parseResponseBody($response->getBody());
        $result = array_merge($result, compact('resolveParams'));

        if ($response->getStatusCode() !== 200) {
            throw $this->makeException('DigiD: invalid response.');
        }

        $result_code = $result['result_code'] ?? null;

        if ($result_code == self::DIGID_CANCELLED) {
            throw $this->makeException('Digid API Request canceled.', $result_code);
        }

        if (!$this->validateVerifyCredentialsResponse($result)) {
            throw $this->makeException('Digid invalid verify credentials request, response body.', $result_code);
        }

        if ($result_code == self::DIGID_SUCCESS) {
            return new DigidAuthResolveData($result['uid'], $result);
        }

        throw $this->makeException('Digid API error code received.', $result_code);
    }

    /**
     * @param string|null $app_id
     * @return DigIdCgiRepo
     */
    public function setAppId(?string $app_id): self
    {
        $this->app_id = $app_id;

        return $this;
    }

    /**
     * @param string|null $a_select_server
     * @return $this
     */
    public function setASelectServer(?string $a_select_server): self
    {
        $this->a_select_server = $a_select_server;

        return $this;
    }

    /**
     * @param string|null $shared_secret
     * @return DigIdCgiRepo
     */
    public function setSharedSecret(?string $shared_secret): self
    {
        $this->shared_secret = $shared_secret;

        return $this;
    }

    /**
     * @param string|null $trusted_certificate
     * @return DigIdCgiRepo
     */
    public function setTrustedCertificate(?string $trusted_certificate): self
    {
        $this->trusted_certificate = $trusted_certificate;

        return $this;
    }

    /**
     * @param Request $request
     * @param string $session_secret
     * @return bool
     */
    public function validateResolveResponse(Request $request, string $session_secret): bool
    {
        return $request->get('session_secret') !== $session_secret;
    }

    /**
     * @param string $uri
     * @param string $method
     * @param ClientTls|null $clientTls
     * @throws DigIdException
     * @return ResponseInterface|null
     */
    protected function makeCall(
        string $uri,
        string $method = 'get',
        ?ClientTls $clientTls = null,
    ): ?ResponseInterface {
        $tlsCert = $clientTls ? new TmpFile($clientTls->getCert()) : null;
        $tlsKey = $clientTls ? new TmpFile($clientTls->getKey()) : null;

        $certificate = $this->makeTrustedCertificate();
        $options = $this->makeRequestOptions($certificate, $tlsCert, $tlsKey);

        try {
            $response = (new Client())->request($method, $uri, $options);
        } catch (Throwable) {
            throw $this->makeException('Digid API not responding.', self::DIGID_API_NOT_RESPONDING);
        } finally {
            $certificate && $certificate->close();
            $tlsCert && $tlsCert->close();
            $tlsKey && $tlsKey->close();
        }

        if (!isset($response)) {
            throw $this->makeException('No response.', self::DIGID_API_NOT_RESPONDING);
        }

        return $response;
    }

    /**
     * @throws DigIdException
     * @return string
     */
    private function getApiUrl(): string
    {
        if ($this->environment == self::ENV_SANDBOX) {
            return self::URL_API_SANDBOX;
        } elseif ($this->environment == self::ENV_PRODUCTION) {
            return self::URL_API_PRODUCTION;
        }
        throw $this->makeException('Invalid environment.');

    }

    /**
     * @return array
     */
    private function getAuthParams(): array
    {
        return [
            'app_id' => $this->app_id,
            'shared_secret' => $this->shared_secret,
            'a-select-server' => $this->a_select_server,
        ];
    }

    /**
     * @param array $params
     * @return array
     */
    private function makeAuthorizedRequest(array $params): array
    {
        return array_merge($this->getAuthParams(), $params);
    }

    /**
     * @param array $params
     * @throws DigIdException
     * @return string
     */
    private function makeRequestUrl(array $params): string
    {
        return sprintf('%s?%s', $this->getApiUrl(), http_build_query($params));
    }

    /**
     * @param $response
     * @return array
     */
    private function parseResponseBody($response): array
    {
        $result = [];
        parse_str($response, $result);

        return $result;
    }

    /**
     * @param $result
     * @return bool
     */
    private function validateAuthRequestResponse($result): bool
    {
        // Should be alphanumeric.
        $check = isset($result['rid']) && ctype_alnum($result['rid']);

        // URL.
        $check = $check && isset($result['as_url']) && $result['as_url'];

        // Should be alphanumeric.
        $check = $check && isset($result['a-select-server']) && ctype_alnum($result['a-select-server']);

        // Should be numeric and 4 characters.
        return $check && isset($result['result_code']) && ctype_digit($result['result_code']);
    }

    /**
     * @param $result
     * @return bool
     */
    private function validateVerifyCredentialsResponse($result): bool
    {
        // Should be numeric.
        $check = isset($result['uid']) && ctype_digit($result['uid']);

        // Should be alphanumeric.
        $check = $check && isset($result['app_id']) && ctype_alnum($result['app_id']);

        // Should be alphanumeric.
        $check = $check && isset($result['organization']) && ctype_alnum($result['organization']);

        // Should be numeric and 2 characters.
        $check = $check && isset($result['betrouwbaarheidsniveau']) && ctype_digit($result['betrouwbaarheidsniveau']);

        // Should be alphanumeric.
        $check = $check && isset($result['rid']) && ctype_alnum($result['rid']);

        // Should be alphanumeric.
        $check = $check && isset($result['a-select-server']) && ctype_alnum($result['a-select-server']);

        // Should be numeric and 4 characters.
        return $check && isset($result['result_code']) && ctype_digit($result['result_code']);
    }

    /**
     * @param TmpFile|bool|null $cert
     * @param TmpFile|null $clientTlsCert
     * @param TmpFile|null $clientTlsKey
     * @return array
     */
    private function makeRequestOptions(
        TmpFile|false|null $cert,
        ?TmpFile $clientTlsCert = null,
        ?TmpFile $clientTlsKey = null,
    ): array {
        $options = [];

        if ($cert !== null) {
            $options['verify'] = $cert ? $cert->path() : false;
        }

        if ($clientTlsCert && $clientTlsKey) {
            $options['cert'] = $clientTlsCert->path();
            $options['ssl_key'] = $clientTlsKey->path();
        }

        return $options;
    }

    /**
     * @return TmpFile|false|null
     */
    private function makeTrustedCertificate(): TmpFile|false|null
    {
        if ($this->trusted_certificate === self::DIGID_CERT_DISABLE) {
            return false;
        }

        return $this->trusted_certificate ? new TmpFile($this->trusted_certificate) : null;
    }
}
