<?php

namespace App\Services\BNGService;

use App\Services\BNGService\Data\AuthData;
use App\Services\BNGService\Data\ResponseData;
use App\Services\BNGService\Exceptions\ApiException;
use App\Services\BNGService\Responses\AccessTokenResponseValue;
use App\Services\BNGService\Responses\AccountsValue;
use App\Services\BNGService\Responses\Balances;
use App\Services\BNGService\Responses\BulkPaymentValue;
use App\Services\BNGService\Responses\Consent\AccountConsentValue;
use App\Services\BNGService\Responses\Consent\BulkPaymentConsentValue;
use App\Services\BNGService\Responses\Entries\BulkPayment;
use App\Services\BNGService\Responses\Entries\Payment;
use App\Services\BNGService\Responses\PaymentValue;
use App\Services\BNGService\Responses\TransactionsValue;
use App\Services\BNGService\Responses\TransactionValue;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

class BNGService
{
    public const string ENV_SANDBOX = 'sandbox';
    public const string ENV_PRODUCTION = 'production';

    public const string URL_SANDBOX = 'https://api.xs2a-sandbox.bngbank.nl';
    public const string URL_PRODUCTION = 'https://api.xs2a.bngbank.nl';

    public const string ENDPOINT_TOKEN = '/token';
    public const string ENDPOINT_AUTHORISE = '/authorise';
    public const string ENDPOINT_AUTHORISE_ACCOUNT = '/authorise';

    public const string ENDPOINT_CONSENT = '/api/v1/consents';
    public const string ENDPOINT_ACCOUNTS = '/api/v1/accounts';
    public const string ENDPOINT_PAYMENT = '/api/v1/payments/sepa-credit-transfers';
    public const string ENDPOINT_PAYMENT_BULK = '/api/v1/bulk-payments/pain.001-sepa-credit-transfers';

    protected string $env;
    protected ?string $keyId;
    protected ?string $clientId;
    protected ?string $tlsCertificate;
    protected ?string $tlsCertificateKey;
    protected ?string $signatureCertificate;
    protected ?string $signatureCertificateKey;
    protected ?array $config;

    protected ?string $authRedirectUrl;

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
     * @throws ApiException
     * @return PaymentValue
     * @noinspection PhpUnused
     */
    public function payment(Payment $payment): PaymentValue
    {
        $url = $this->getEndpoint('payment');
        $res = new ResponseData($this->requestJson('post', $url, [
            'debtorAccount' => $payment->getDebtor()->toArray(),
            'instructedAmount' => $payment->getAmount()->toArray(),
            'creditorAccount' => $payment->getCreditor()->toArray(),
            'creditorName' => $payment->getCreditor()->getName(),
            'requestedExecutionDate' => $payment->getRequestedExecutionDate(),
            'remittanceInformationUnstructured' => $payment->getDescription(),
        ]));

        return new PaymentValue($res);
    }

    /**
     * @param BulkPayment $bulkPayment
     * @throws ApiException
     * @return BulkPaymentConsentValue
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
     * @throws ApiException
     * @return AccessTokenResponseValue
     */
    public function exchangeAuthCode(string $code, AuthData $data): AccessTokenResponseValue
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

        return new AccessTokenResponseValue(new ResponseData($res));
    }

    /**
     * @param string $paymentId
     * @param string $accessToken
     * @throws ApiException
     * @return BulkPaymentValue
     */
    public function getBulkDetails(string $paymentId, string $accessToken): BulkPaymentValue
    {
        $url = $this->getEndpoint('payment_bulk', [$paymentId]);
        $res = $this->requestJson('get', $url, null, [
            'Authorization' => sprintf('Bearer %s', $accessToken),
        ]);

        return new BulkPaymentValue(new ResponseData($res));
    }

    /**
     * @param string $paymentId
     * @param string $accessToken
     * @throws ApiException
     * @return BulkPaymentValue
     * @noinspection PhpUnused
     */
    public function getBulkDetailsStatus(string $paymentId, string $accessToken): BulkPaymentValue
    {
        $url = $this->getEndpoint('payment_bulk', [$paymentId, 'status']);
        $res = $this->requestJson('get', $url, null, [
            'Authorization' => sprintf('Bearer %s', $accessToken),
        ]);

        return new BulkPaymentValue(new ResponseData($res));
    }

    /**
     * @param string $consentId
     * @param string $accessToken
     * @throws ApiException
     * @return AccountsValue
     * @noinspection PhpUnused
     */
    public function getAccounts(string $consentId, string $accessToken): AccountsValue
    {
        $url = $this->getEndpoint('accounts');
        $res = $this->requestJson('get', $url, null, [
            'Authorization' => sprintf('Bearer %s', $accessToken),
            'Consent-ID' => $consentId,
        ]);

        return new AccountsValue(new ResponseData($res));
    }

    /**
     * @param string $accountId
     * @param string $consentId
     * @param string $accessToken
     * @param array $params
     * @throws ApiException
     * @return TransactionsValue
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
            'Authorization' => sprintf('Bearer %s', $accessToken),
            'Consent-ID' => $consentId,
        ]);

        return new TransactionsValue(new ResponseData($res));
    }

    /**
     * @param string $accountId
     * @param string $consentId
     * @param string $accessToken
     * @param string $transactionId
     * @throws ApiException
     * @return ?TransactionValue
     */
    public function getTransaction(
        string $accountId,
        string $consentId,
        string $accessToken,
        string $transactionId,
    ): ?TransactionValue {
        $url = $this->getEndpoint('accounts', [$accountId, 'transactions', $transactionId]);

        $res = $this->requestJson('get', $url, null, [
            'Authorization' => sprintf('Bearer %s', $accessToken),
            'Consent-ID' => $consentId,
        ]);

        if ($res->getStatusCode() == 200) {
            $data = $res->getBody()->getContents();

            return new TransactionValue(new ResponseData(
                is_string($data) ? json_decode($data, true)['transactionDetails'] ?? null : $data,
                $res->getStatusCode(),
                $res->getHeaders()
            ));
        }

        return null;
    }

    /**
     * @param string $accountId
     * @param string $consentId
     * @param string $accessToken
     * @param array $params
     * @throws ApiException
     * @return Balances
     */
    public function getBalance(
        string $accountId,
        string $consentId,
        string $accessToken,
        array $params = []
    ): Balances {
        $url = $this->getEndpoint('accounts', [$accountId, 'balances'], $params);
        $res = $this->requestJson('get', $url, null, [
            'Authorization' => sprintf('Bearer %s', $accessToken),
            'Consent-ID' => $consentId,
        ]);

        return new Balances(new ResponseData($res));
    }

    /**
     * @param string $redirectToken
     * @param array $params
     * @throws ApiException
     * @return AccountConsentValue
     */
    public function makeAccountConsentRequest(string $redirectToken, array $params = []): AccountConsentValue
    {
        $url = $this->getEndpoint('consent');
        $res = $this->requestJson('post', $url, array_merge_recursive([
            'access' => [
                'accounts' => null,
                'balances' => null,
                'transactions' => null,
                'availableAccounts' => null,
                'availableAccountsWithBalances' => null,
                'allPsd2' => 'allAccounts',
            ],
            'combinedServiceIndicator' => false,
            'recurringIndicator' => true,
            'validUntil' => date('Y-m-d', strtotime('+2 years')),
            'frequencyPerDay' => 4,
        ], $params));

        return new AccountConsentValue(new ResponseData($res), $redirectToken, $this);
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
            case 'token': $endpoint = static::ENDPOINT_TOKEN;
                break;
            case 'consent': $endpoint = static::ENDPOINT_CONSENT;
                break;
            case 'accounts': $endpoint = static::ENDPOINT_ACCOUNTS;
                break;
            case 'authorise': $endpoint = static::ENDPOINT_AUTHORISE;
                break;
            case 'authorise_account': $endpoint = static::ENDPOINT_AUTHORISE_ACCOUNT;
                break;
            case 'payment': $endpoint = static::ENDPOINT_PAYMENT;
                break;
            case 'payment_bulk': $endpoint = static::ENDPOINT_PAYMENT_BULK;
                break;
            default: return null;
        }

        return implode('?', array_filter([
            $url . implode('/', array_merge([$endpoint], $segments)),
            $query ? http_build_query($query) : '',
        ]));
    }

    /**
     * @param int $length
     * @return string
     */
    public function makeToken(int $length = 100): string
    {
        try {
            return bin2hex(random_bytes($length / 2));
        } catch (Throwable) {
            throw new RuntimeException('Failed to generate token.');
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
     * @return string|null
     */
    public function getAuthRedirectUrl(): ?string
    {
        return $this->authRedirectUrl;
    }

    /**
     * @param string $message
     * @param Throwable|null $e
     * @return void
     */
    public static function logError(string $message, ?Throwable $e): void
    {
        Log::channel('bng')->error(implode("\n", array_filter([
            $message,
            $e?->getMessage(),
            $e?->getTraceAsString(),
        ])));
    }

    /**
     * @param string $method
     * @param string $url
     * @param string $data
     * @param array $headers
     * @throws ApiException
     * @return ResponseInterface
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
     * @throws ApiException
     * @return ResponseInterface
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
     * @throws ApiException
     * @return ResponseInterface
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
     * @throws ApiException
     * @return ResponseInterface
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
        } catch (Throwable $e) {
            throw new ApiException($e->getMessage(), $e->getCode());
        } finally {
            if (isset($tlsCertificate)) {
                $tlsCertificate->close();
            }

            if (isset($tlsCertificateKey)) {
                $tlsCertificateKey->close();
            }
        }
    }

    /**
     * @param string $method
     * @param string $url
     * @param string $request_id
     * @param string $bodyString
     * @param string $contentType
     * @param array $cHeaders
     * @return array
     */
    protected function makeHeaders(
        string $method,
        string $url,
        string $request_id,
        string $bodyString = '',
        string $contentType = 'application/json',
        array $cHeaders = []
    ): array {
        $date = gmdate('r', time());
        $digest = $this->makeDigest($bodyString);

        $headers = array_merge([
            'request-target' => $url,
            'Accept' => 'application/json',
            'Content-Type' => $contentType,
            'Date' => $date,
            'Digest' => $digest,
            'X-Request-ID' => $request_id,
            'PSU-IP-Address' => $this->psuIpAddress(),
        ], $cHeaders);

        return array_merge($headers, [
            'Signature' => $this->makeSignature(strtolower($method), $headers),
            'TPP-Signature-Certificate' => $this->getSignatureCertificate(),
        ]);
    }

    /**
     * @return string|null
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
        return str_replace(["\n", "\r"], '', $this->signatureCertificate);
    }

    /**
     * @param string $method
     * @param array $headers
     * @return string
     */
    protected function makeSignature(string $method = 'post', array $headers = []): string
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

        $signatureHeaders = array_map(function ($header) use ($headers) {
            return sprintf('%s: %s', strtolower($header), $headers[$header]);
        }, array_keys($headers));

        $signatureHeaders = str_replace(
            'request-target:',
            "(request-target): $method",
            implode("\n", $signatureHeaders)
        );

        $signatureHeadersList = implode(' ', array_map(function ($header) {
            return strtolower(explode(': ', $header)[0]);
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
     * @return string|null
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
        return 'SHA-256=' . base64_encode(hash('sha256', $body, true));
    }

    /**
     * @return string
     */
    protected function makeRequestId(): string
    {
        return Str::uuid();
    }
}
