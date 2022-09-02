<?php

namespace App\Services\BNGService;

use App\Services\BNGService\Data\AuthData;
use App\Services\BNGService\Data\ResponseData;
use App\Services\BNGService\Exceptions\ApiException;
use App\Services\BNGService\Responses\AccountsValue;
use App\Services\BNGService\Responses\Balances;
use App\Services\BNGService\Responses\BulkPaymentValue;
use App\Services\BNGService\Responses\BulkPaymentTokenValue;
use App\Services\BNGService\Responses\Consent\AccountConsentValue;
use App\Services\BNGService\Responses\Consent\BulkPaymentConsentValue;
use App\Services\BNGService\Responses\Entries\BulkPayment;
use App\Services\BNGService\Responses\Entries\Payment;
use App\Services\BNGService\Responses\PaymentValue;
use App\Services\BNGService\Responses\TransactionsValue;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use RuntimeException;
use Psr\Http\Message\ResponseInterface;

class BNGService
{
    public const ENV_SANDBOX = 'sandbox';
    public const ENV_PRODUCTION = 'production';

    public const URL_SANDBOX = 'https://api.xs2a-sandbox.bngbank.nl';
    public const URL_PRODUCTION = 'https://api.xs2a.bngbank.nl';

    public const ENDPOINT_TOKEN = '/token';
    public const ENDPOINT_AUTHORISE = '/authorise';
    public const ENDPOINT_AUTHORISE_ACCOUNT = '/authorise';

    public const ENDPOINT_CONSENT = '/api/v1/consents';
    public const ENDPOINT_ACCOUNTS = '/api/v1/accounts';
    public const ENDPOINT_PAYMENT = '/api/v1/payments/sepa-credit-transfers';
    public const ENDPOINT_PAYMENT_BULK = '/api/v1/bulk-payments/pain.001-sepa-credit-transfers';

    protected $env;
    protected $keyId;
    protected $clientId;
    protected $tlsCertificate;
    protected $tlsCertificateKey;
    protected $signatureCertificate;
    protected $signatureCertificateKey;
    protected $config;

    protected $authRedirectUrl;

    /**
     * @param string $env
     * @param array $config
     */
    public function __construct(string $env, array $config = [])
    {
        $this->env = $env;
        $this->config = $config;
        $this->keyId = $config['keyId'];
        $this->clientId = $config['clientId'];
        $this->tlsCertificate = $config['tlsCertificate'];
        $this->tlsCertificateKey = $config['tlsCertificateKey'];
        $this->signatureCertificate = $config['signatureCertificate'];
        $this->signatureCertificateKey = $config['signatureCertificateKey'];
        $this->authRedirectUrl = $config['authRedirectUrl'];
    }

    /**
     * @param Payment $payment
     * @return PaymentValue
     * @throws ApiException
     * @noinspection PhpUnused
     */
    public function payment(Payment $payment): PaymentValue
    {
        $url = $this->getEndpoint('payment');
        $res = new ResponseData($this->requestJson('post', $url, [
            'debtorAccount' => $payment->getDebtor()->toArray(),
            'instructedAmount' => $payment->getAmount()->toArray(),
            'creditorAccount' => $payment->getCreditor()->toArray(),
            'creditorName'=> $payment->getCreditor()->getName(),
            'requestedExecutionDate'=> $payment->getRequestedExecutionDate(),
            'remittanceInformationUnstructured' => $payment->getDescription(),
        ]));

        return new PaymentValue($res);
    }

    /**
     * @param BulkPayment $bulkPayment
     * @return BulkPaymentConsentValue
     * @throws ApiException
     */
    public function bulkPayment(BulkPayment $bulkPayment): BulkPaymentConsentValue
    {
        $url = $this->getEndpoint('payment_bulk');
        $res = new ResponseData($this->requestXml('post', $url, $bulkPayment->toXml()));

        return new BulkPaymentConsentValue($res, $bulkPayment->getRedirectToken(), $this);
    }

    /**
     * @param string $code
     * @param AuthData $data
     * @return BulkPaymentTokenValue
     * @throws ApiException
     */
    public function exchangeAuthCode(string $code, AuthData $data): BulkPaymentTokenValue
    {
        $auth_params = $data->getParams();

        $url = $this->getEndpoint('token');
        $res = $this->requestFormUrlEncoded('post', $url, http_build_query([
            'client_id' => $this->clientId(),
            'grant_type' => 'authorization_code',
            'code' => $code,
            'state' => $auth_params['state'],
            'redirect_uri' => $auth_params['redirect_uri'],
            'code_verifier' => $auth_params['code_challenge'],
        ]));

        return new BulkPaymentTokenValue(new ResponseData($res));
    }

    /**
     * @param string $paymentId
     * @param string $accessToken
     * @return BulkPaymentValue
     * @throws ApiException
     */
    public function getBulkDetails(
        string $paymentId,
        string $accessToken
    ): BulkPaymentValue {
        $url = $this->getEndpoint('payment_bulk', [$paymentId]);
        $res = $this->requestJson('get', $url, null, [
            "Authorization" => sprintf("Bearer %s", $accessToken),
        ]);

        return new BulkPaymentValue(new ResponseData($res));
    }

    /**
     * @param string $consentId
     * @param string $accessToken
     * @return AccountsValue
     * @throws ApiException
     */
    public function getAccounts(string $consentId, string $accessToken): AccountsValue
    {
        $url = $this->getEndpoint('accounts');
        $res = $this->requestJson('get', $url, null, [
            "Authorization" => sprintf("Bearer %s", $accessToken),
            "Consent-ID" => $consentId,
        ]);

        return new AccountsValue(new ResponseData($res));
    }

    /**
     * @param string $accountId
     * @param string $consentId
     * @param string $accessToken
     * @param array $params
     * @return TransactionsValue
     * @throws ApiException
     */
    public function getTransactions(
        string $accountId,
        string $consentId,
        string $accessToken,
        array $params = []
    ): TransactionsValue {
        $dateFrom = date('Y-m-d', strtotime('-1 year'));
        $params = array_merge(compact('dateFrom'), $params);
        $url = $this->getEndpoint('accounts', [$accountId, 'transactions'], $params);

        $res = $this->requestJson('get', $url, null, [
            "Authorization" => sprintf("Bearer %s", $accessToken),
            "Consent-ID" => $consentId,
        ]);

        return new TransactionsValue(new ResponseData($res));
    }

    /**
     * @param string $accountId
     * @param string $consentId
     * @param string $accessToken
     * @param array $params
     * @return Balances
     * @throws ApiException
     */
    public function getBalance(
        string $accountId,
        string $consentId,
        string $accessToken,
        array $params = []
    ): Balances {
        $url = $this->getEndpoint('accounts', [$accountId, 'balances'], $params);
        $res = $this->requestJson('get', $url, null, [
            "Authorization" => sprintf("Bearer %s", $accessToken),
            "Consent-ID" => $consentId,
        ]);

        return new Balances(new ResponseData($res));
    }

    /**
     * @param string $redirectToken
     * @param array $params
     * @return AccountConsentValue
     * @throws ApiException
     */
    public function makeAccountConsentRequest(string $redirectToken, array $params = []): AccountConsentValue
    {
        $url = $this->getEndpoint('consent');
        $res = $this->requestJson('post', $url, array_merge_recursive([
            "access" => [
                "accounts" => null,
                "balances" => null,
                "transactions" => null,
                "availableAccounts" => null,
                "availableAccountsWithBalances" => null,
                "allPsd2" => "allAccounts",
            ],
            "combinedServiceIndicator" => false,
            "recurringIndicator" => true,
            "validUntil" => date('Y-m-d', strtotime('+2 years')),
            "frequencyPerDay" => 4,
        ], $params));

        return new AccountConsentValue(new ResponseData($res), $redirectToken, $this);
    }

    /**
     * @param string $method
     * @param string $url
     * @param string $data
     * @param array $headers
     * @return ResponseInterface
     * @throws ApiException
     */
    protected function requestFormUrlEncoded(string $method, string $url, string $data, array $headers = []): ResponseInterface
    {
        return $this->request($method, $url, $data, 'application/x-www-form-urlencoded;charset=UTF-8', $headers);
    }

    /**
     * @param string $method
     * @param string $url
     * @param string $data
     * @param array $headers
     * @return ResponseInterface
     * @throws ApiException
     */
    protected function requestXml(string $method, string $url, string $data, array $headers = []): ResponseInterface
    {
        return $this->request($method, $url, $data, 'application/xml', $headers);
    }

    /**
     * @param string $method
     * @param string $url
     * @param array|null $data
     * @param array $headers
     * @return ResponseInterface
     * @throws ApiException
     */
    protected function requestJson(string $method, string $url, ?array $data = null, array $headers = []): ResponseInterface
    {
        return $this->request($method, $url, $data ? json_encode($data) : '', 'application/json', $headers);
    }

    /**
     * @param string $method
     * @param string $url
     * @param string $data
     * @param string $contentType
     * @param array $headers
     * @return ResponseInterface
     * @throws ApiException
     */
    protected function request(
        string $method,
        string $url,
        string $data,
        string $contentType = 'application/json',
        array $headers = []
    ): ResponseInterface {
        $client = new Client();
        $requestId = $this->makeRequestId();
        $bodyString = $data;

        try {
            $tlsCertificate = new TmpFile($this->tlsCertificate);
            $tlsCertificateKey = new TmpFile($this->tlsCertificateKey);

            return $client->request($method, $url, [
                'headers' => $this->makeHeaders($method, $url, $requestId, $bodyString, $contentType, $headers),
                'body' => $bodyString,
                'cert' => $tlsCertificate->path(),
                'ssl_key' => $tlsCertificateKey->path(),
            ]);
        } catch (\Throwable $e) {
            throw new ApiException($e->getMessage(), $e->getCode());
        } finally {
            $tlsCertificate->close();
            $tlsCertificateKey->close();
        }
    }

    /**
     * @param string $method
     * @param string $url
     * @param string $request_id
     * @param string $bodyString
     * @param string $contentType
     * @param array $cHeaders
     * @return mixed
     */
    protected function makeHeaders(
        string $method,
        string $url,
        string $request_id,
        string $bodyString = "",
        string $contentType = 'application/json',
        array $cHeaders = []
    ) {
        $date = gmdate('r', time());
        $digest = $this->makeDigest($bodyString);

        $headers = array_merge([
            "request-target" => $url,
            "Accept" => "application/json",
            "Content-Type" => $contentType,
            "Date" => $date,
            "Digest" => $digest,
            "X-Request-ID" => $request_id,
            "PSU-IP-Address" => $this->psuIpAddress(),
        ], $cHeaders);

        return array_merge($headers, [
            "Signature" => $this->makeSignature(strtolower($method), $headers),
            "TPP-Signature-Certificate" => $this->getSignatureCertificate(),
        ]);
    }

    /**
     * @return string
     */
    protected function psuIpAddress(): ?string
    {
        return $this->config['psuIpAddress'] ?? null;
    }

    /**
     * @return string
     */
    protected function getSignatureCertificate(): string
    {
        return str_replace(["\n", "\r"], "", $this->signatureCertificate);
    }

    /**
     * @param $method
     * @param $headers
     * @return string
     */
    protected function makeSignature($method = 'post', $headers = []): string
    {
        $private_key = openssl_pkey_get_private($this->signatureCertificateKey);
        $signatureHeaderNames = ['request-target', 'Date', 'Digest', 'X-Request-ID'];

        foreach (array_keys($headers) as $header) {
            if (!in_array($header, $signatureHeaderNames)) {
                unset($headers[$header]);
            }
        }

        $headers['request-target'] = implode('?', array_filter([
            parse_url($headers['request-target'])['path'] ?? '',
            parse_url($headers['request-target'])['query'] ?? '',
        ]));

        $signatureHeaders = array_map(function($header) use ($headers) {
            return sprintf("%s: %s", strtolower($header), $headers[$header]);
        }, array_keys($headers));

        $signatureHeaders = str_replace(
            'request-target:',
            "(request-target): $method",
            implode("\n", $signatureHeaders)
        );

        $signatureHeadersList = implode(' ', array_map(function ($header) {
            return strtolower(explode(": ", $header)[0]);
        }, explode("\n", $signatureHeaders)));

        return sprintf(
            'keyId="%s", algorithm="sha256RSA", headers="%s", signature="%s"',
            $this->getKeyId(),
            $signatureHeadersList,
            $this->encrypt_RSA($signatureHeaders, $private_key)
        );
    }

    /**
     * @return string
     */
    protected function getKeyId(): string
    {
        return $this->keyId;
    }

    /**
     * @param $plainData
     * @param $privateKeyId
     * @return false|string
     */
    protected function encrypt_RSA($plainData, $privateKeyId): ?string
    {
        $encrypted = '';
        $encryptionOk = openssl_sign($plainData, $encrypted, $privateKeyId, 'RSA-SHA256');
        openssl_free_key($privateKeyId);

        return $encryptionOk === false ? null : base64_encode($encrypted);
    }

    /**
     * @param string $body
     * @return string
     */
    protected function makeDigest(string $body): string
    {
        return "SHA-256=" . base64_encode(hash("sha256", $body, true));
    }

    /**
     * @param string $name
     * @param array $segments
     * @param array $query
     * @return string|null
     */
    public function getEndpoint(string $name, array $segments = [], array $query = []): ?string
    {
        $url = [
            static::ENV_SANDBOX => static::URL_SANDBOX,
            static::ENV_PRODUCTION => static::URL_PRODUCTION,
        ][$this->env] ?? static::URL_SANDBOX;

        switch ($name) {
            case 'token': $endpoint = static::ENDPOINT_TOKEN; break;
            case 'consent': $endpoint = static::ENDPOINT_CONSENT; break;
            case 'accounts': $endpoint = static::ENDPOINT_ACCOUNTS; break;
            case 'authorise': $endpoint = static::ENDPOINT_AUTHORISE; break;
            case 'authorise_account': $endpoint = static::ENDPOINT_AUTHORISE_ACCOUNT; break;
            case 'payment': $endpoint = static::ENDPOINT_PAYMENT; break;
            case 'payment_bulk': $endpoint = static::ENDPOINT_PAYMENT_BULK; break;
            default: return null;
        }

        return implode("?", array_filter([
            $url . implode("/", array_merge([$endpoint], $segments)),
            $query ? http_build_query($query) : '',
        ]));
    }

    /**
     * @return string
     */
    protected function makeRequestId(): string
    {
        return Str::uuid();
    }

    /**
     * @param int $length
     * @return string
     */
    public function makeToken(int $length = 100): string
    {
        try {
            return bin2hex(random_bytes($length / 2));
        } catch (\Throwable $e) {
            throw new RuntimeException("Failed to generate token.");
        }
    }

    /**
     * @return string
     */
    public function clientId(): string
    {
        return $this->clientId;
    }

    /**
     * @return mixed
     */
    public function getAuthRedirectUrl()
    {
        return $this->authRedirectUrl;
    }
}