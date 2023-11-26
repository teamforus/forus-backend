<?php


namespace App\Services\MollieService;

use App\Services\MollieService\Exceptions\MollieException;
use App\Services\MollieService\Interfaces\MollieToken;
use App\Services\MollieService\Models\MollieConnection;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use League\OAuth2\Client\Grant\RefreshToken;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\BalanceTransactionCollection;
use Mollie\Api\Resources\BaseCollection;
use Mollie\Api\Resources\CurrentProfile;
use Mollie\Api\Resources\Method;
use Mollie\Api\Resources\MethodCollection;
use Mollie\Api\Resources\Onboarding;
use Mollie\Api\Resources\Organization;
use Mollie\Api\Resources\Payment;
use Mollie\Api\Resources\Profile;
use Mollie\Api\Resources\ProfileCollection;
use Mollie\Api\Resources\Refund;
use Mollie\Api\Resources\RefundCollection;
use Mollie\OAuth2\Client\Provider\Mollie;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class MollieService
{
    protected bool $testMode = false;

    protected ?string $appClientId;
    protected ?string $appClientSecret;
    protected ?string $redirectUri;
    protected ?string $webhookUri;

    public const PAYMENT_METHOD_IDEAL = 'ideal';

    /**
     * @param MollieToken|null $token
     */
    protected function __construct(
        protected ?MollieToken $token = null,
    ) {
        $this->testMode = Config::get('mollie.test_mode');
        $this->appClientId = Config::get('mollie.client_id');
        $this->appClientSecret = Config::get('mollie.client_secret');

        $this->redirectUri = url(Config::get('mollie.redirect_url'));
        $this->webhookUri = url(Config::get('mollie.webhook_url'));
    }

    /**
     * @param MollieToken $mollieToken
     * @return static
     */
    public static function make(MollieToken $mollieToken): static
    {
        return new static($mollieToken);
    }

    /**
     * @return Mollie
     */
    public function getProvider(): Mollie
    {
        return new Mollie([
            'clientId' => $this->appClientId,
            'clientSecret' => $this->appClientSecret,
            'redirectUri' => $this->redirectUri
        ]);
    }

    /**
     * @return array
     */
    protected function getRequiredScopes(): array
    {
        return [
            Mollie::SCOPE_ORGANIZATIONS_READ,
            Mollie::SCOPE_PROFILES_READ,
            Mollie::SCOPE_PROFILES_WRITE,
            Mollie::SCOPE_PAYMENTS_READ,
            Mollie::SCOPE_PAYMENTS_WRITE,
            Mollie::SCOPE_ONBOARDING_READ,
            Mollie::SCOPE_ONBOARDING_WRITE,
            Mollie::SCOPE_REFUNDS_READ,
            Mollie::SCOPE_REFUNDS_WRITE,
            Mollie::SCOPE_BALANCES_READ,
        ];
    }

    /**
     * @param string $state
     * @return string
     */
    public function mollieConnect(string $state): string
    {
        return $this->getProvider()->getAuthorizationUrl([
            'approval_prompt' => 'force',
            'state' => $state,
            'scope' => $this->getRequiredScopes(),
        ]);
    }

    /**
     * @param string $code
     * @param string $state
     * @return MollieConnection|null
     * @throws MollieException
     */
    public function exchangeOauthCode(string $code, string $state): ?MollieConnection
    {
        try {
            return $this->processConnectionByToken(
                $this->getProvider()->getAccessToken('authorization_code', compact('code')),
                $state,
            );
        } catch (IdentityProviderException $e) {
            $this->processException($e, 'exchangeOauthCode');
        }
    }

    /**
     * @param AccessToken $token
     * @param string $state
     * @return MollieConnection|null
     */
    private function processConnectionByToken(AccessToken $token, string $state): ?MollieConnection
    {
        return MollieConnection::firstWhere('state_code', $state)?->updateConnectionByToken(
            $token,
            $this->getProvider()->getResourceOwner($token)->toArray(),
        );
    }

    /**
     * @param string $refreshToken
     * @return AccessToken|AccessTokenInterface
     * @noinspection PhpUnused
     * @throws MollieException
     */
    public function refreshToken(string $refreshToken): AccessTokenInterface|AccessToken
    {
        try {
            return $this->getProvider()->getAccessToken(new RefreshToken(), [
                'refresh_token' => $refreshToken,
            ]);
        } catch (IdentityProviderException $e) {
            $this->processException($e, 'refreshToken');
        }
    }

    /**
     * @param string $state
     * @param string $name
     * @param array $owner
     * @param array $address
     * @return string
     * @throws MollieException
     */
    public function createClientLink(
        string $state,
        string $name,
        array $owner = [],
        array $address = [],
    ): string {
        try {
            return $this->getMollie()->clientLinks->create([
                "name" => $name,
                "owner" => $owner,
                "address" => $address,
            ])->getRedirectUrl($this->appClientId, $state, $this->getRequiredScopes());
        } catch (ApiException $e) {
            $this->processApiException($e, 'createClientLink');
        } catch (Throwable $e) {
            $this->processException($e, 'createClientLink');
        }
    }

    /**
     * @return Organization
     * @throws MollieException
     */
    public function getOrganization(): Organization
    {
        try {
            return $this->getMollie()->organizations->current();
        } catch (ApiException $e) {
            $this->processApiException($e, 'readOrganization');
        }
    }

    /**
     * @return Onboarding
     * @noinspection PhpUnused
     * @throws MollieException
     */
    public function getOnboardingState(): Onboarding
    {
        try {
            return $this->getMollie()->onboarding->get();
        } catch (ApiException $e) {
            $this->processApiException($e, 'readOnboardingState');
        }
    }

    /**
     * @param array $attributes
     * @return Profile
     * @noinspection PhpUnused
     * @throws MollieException
     */
    public function createProfile(array $attributes = []): Profile
    {
        try {
            return $this->getMollie()->profiles->create(array_merge(Arr::only($attributes, [
                'name', 'email', 'phone', 'website',
            ]), [
                'mode' => $this->testMode ? 'test' : 'live',
            ]));
        } catch (ApiException $e) {
            $this->processApiException($e, 'createProfile');
        }
    }

    /**
     * @param string $profileId
     * @return CurrentProfile|Profile
     * @noinspection PhpUnused
     * @throws MollieException
     */
    public function readProfile(string $profileId): Profile|CurrentProfile
    {
        try {
            return $this->getMollie()->profiles->get($profileId);
        } catch (ApiException $e) {
            $this->processApiException($e, 'readProfile');
        }
    }

    /**
     * @param string $profileId
     * @param array $attributes
     * @return Profile
     * @noinspection PhpUnused
     * @throws MollieException
     */
    public function updateProfile(string $profileId, array $attributes = []): Profile
    {
        try {
            return $this->getMollie()->profiles->update($profileId, $attributes);
        } catch (ApiException $e) {
            $this->processApiException($e, 'updateProfile');
        }
    }

    /**
     * @return ProfileCollection|null
     * @noinspection PhpUnused
     * @throws MollieException
     */
    public function readAllProfiles(): ?ProfileCollection
    {
        try {
            return $this->getMollie()->profiles->page();
        } catch (ApiException $e) {
            $this->processApiException($e, 'readAllProfiles');
        }
    }

    /**
     * @param string $profileId
     * @return BaseCollection|MethodCollection
     * @noinspection PhpUnused
     * @throws MollieException
     */
    public function readAllPaymentMethods(string $profileId): BaseCollection|MethodCollection
    {
        try {
            return $this->getMollie()->methods->allAvailable([
                'profileId' => $profileId,
                'testmode' => $this->testMode,
            ]);
        } catch (ApiException $e) {
            $this->processApiException($e, 'readAllPaymentMethods');
        }
    }

    /**
     * @param string $profileId
     * @return BaseCollection|MethodCollection
     * @noinspection PhpUnused
     * @throws MollieException
     */
    public function readActivePaymentMethods(string $profileId): BaseCollection|MethodCollection
    {
        try {
            return $this->getMollie()->methods->allActive([
                'profileId' => $profileId,
                'testmode' => $this->testMode
            ]);
        } catch (ApiException $e) {
            $this->processApiException($e, 'readActivePaymentMethods');
        }
    }

    /**
     * @param string $profileId
     * @param string $method
     * @return Method
     * @noinspection PhpUnused
     * @throws MollieException
     */
    public function enablePaymentMethod(string $profileId, string $method): Method
    {
        try {
            return $this->getMollie()->profiles->get($profileId)->enableMethod($method);
        } catch (ApiException $e) {
            $this->processApiException($e, 'enablePaymentMethod');
        }
    }

    /**
     * @param string $profileId
     * @param string $method
     * @return Method
     * @noinspection PhpUnused
     * @throws MollieException
     */
    public function disablePaymentMethod(string $profileId, string $method): Method
    {
        try {
            return $this->getMollie()->profiles->get($profileId)->disableMethod($method);
        } catch (ApiException $e) {
            $this->processApiException($e, 'disablePaymentMethod');
        }
    }

    /**
     * @param string $profileId
     * @param array $attributes
     * @return Payment
     * @throws MollieException
     */
    public function createPayment(string $profileId, array $attributes): Payment
    {
        try {
            return $this->getMollie()->payments->create([
                'profileId' => $profileId,
                'amount' => [
                    "currency" => $attributes['currency'],
                    "value" => currency_format($attributes['amount']),
                ],
                'description' => $attributes['description'],
                'redirectUrl' => $attributes['redirect_url'],
                'cancelUrl' => $attributes['cancel_url'],
                'webhookUrl' => $this->webhookUri,
                'locale' => 'nl_NL',
                'method' => [self::PAYMENT_METHOD_IDEAL],
                'testmode' => $this->testMode,
            ]);
        } catch (ApiException $e) {
            $this->processApiException($e, 'createPayment');
        }
    }

    /**
     * @param string $paymentId
     * @return Payment
     * @noinspection PhpUnused
     * @throws MollieException
     */
    public function getPayment(string $paymentId): Payment
    {
        try {
            return $this->getMollie()->payments->get($paymentId, [
                'testmode' => $this->testMode,
            ]);
        } catch (ApiException $e) {
            $this->processApiException($e, 'readPayment');
        }
    }

    /**
     * @param string $paymentId
     * @return Payment
     * @noinspection PhpUnused
     * @throws MollieException
     */
    public function cancelPayment(string $paymentId): Payment
    {
        try {
            return $this->getMollie()->payments->cancel($paymentId, [
                'testmode' => $this->testMode,
            ]);
        } catch (ApiException $e) {
            $this->processApiException($e, 'cancelPayment');
        }
    }

    /**
     * @param string $paymentId
     * @param array $attributes
     * @return Refund
     * @noinspection PhpUnused
     * @throws MollieException
     */
    public function refundPayment(string $paymentId, array $attributes): Refund
    {
        try {
            return $this->getPayment($paymentId)->refund([
                'amount' => [
                    "currency" => $attributes['currency'],
                    "value" => currency_format($attributes['amount']),
                ],
                'description' => $attributes['description'],
                'testmode' => $this->testMode,
            ]);
        } catch (ApiException $e) {
            $this->processApiException($e, 'refundPayment');
        }
    }

    /**
     * @param string $paymentId
     * @return RefundCollection
     * @throws MollieException
     */
    public function getPaymentRefunds(string $paymentId): RefundCollection
    {
        try {
            return $this->getPayment($paymentId)->refunds();
        } catch (ApiException $e) {
            $this->processApiException($e, 'readPaymentRefund');
        }
    }

    /**
     * @param string $paymentId
     * @param string $refundId
     * @return Refund
     * @noinspection PhpUnused
     * @throws MollieException
     */
    public function readPaymentRefund(string $paymentId, string $refundId): Refund
    {
        try {
            return $this->getPayment($paymentId)->getRefund($refundId, [
                'testmode' => $this->testMode,
            ]);
        } catch (ApiException $e) {
            $this->processApiException($e, 'readPaymentRefund');
        }
    }

    /**
     * @return BalanceTransactionCollection|BaseCollection
     * @noinspection PhpUnused
     * @throws MollieException
     */
    public function readBalanceTransactions(): BalanceTransactionCollection|BaseCollection
    {
        try {
            return $this->getMollie()->balanceTransactions->listForPrimary();
        } catch (ApiException $e) {
            $this->processApiException($e, 'readBalanceTransactions');
        }
    }

    /**
     * @return bool
     */
    public function revokeToken(): bool
    {
        try {
            $this->getProvider()->revokeRefreshToken($this->token->getRefreshToken());
            $this->token->deleteToken();

            return true;
        } catch (GuzzleException $e) {
            static::logError("Failed to revoke refresh token.", $e);
            return false;
        }
    }

    /**
     * @return string|null
     * @throws MollieException
     */
    public function getAccessToken(): ?string
    {
        if ($this->token->isTokenExpired()) {
            $this->token->setAccessToken($this->refreshToken($this->token->getRefreshToken()));
        }

        return $this->token->getAccessToken();
    }

    /**
     * @param ApiException $e
     * @param string $key
     * @return void
     * @throws MollieException
     */
    private function processApiException(ApiException $e, string $key): void
    {
        $body = static::parseResponseBody($e->getResponse());

        try {
            static::logError("$key api error.\n" . json_encode($body, JSON_THROW_ON_ERROR), $e);
        } catch (Throwable) {}

        throw new MollieException(
            Arr::get($body, 'detail', Arr::get($body, 'title', 'Onbekende foutmelding!')),
            $e->getCode(),
        );
    }

    /**
     * @param ApiException $e
     * @param string $key
     * @return void
     * @throws MollieException
     */
    private function processException(Throwable $e, string $key): void
    {
        try {
            static::logError("$key error.\n", $e);
        } catch (Throwable) {}

        throw new MollieException($e->getMessage(), $e->getCode());
    }

    /**
     * @param ResponseInterface|null $response
     * @return array|null
     * @throws MollieException
     */
    protected static function parseResponseBody(?ResponseInterface $response): ?array
    {
        try {
            $body = (string) $response?->getBody();
            $object = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            throw new MollieException('Invalid API response format.');
        }

        return json_last_error() !== JSON_ERROR_NONE ? null : $object;
    }

    /**
     * @throws MollieException
     */
    protected function getMollie(): MollieApiClient
    {
        try {
            return (new MollieApiClient())->setAccessToken($this->getAccessToken());
        } catch (Throwable $e) {
            static::logError("setAccessToken error.", $e);
            throw new MollieException('Unknown error please try later.', 503);
        }
    }

    /**
     * @param string $message
     * @param Throwable|null $e
     * @return void
     */
    public static function logError(string $message, ?Throwable $e): void
    {
        Log::channel('mollie')->error(implode("\n", array_filter([
            $message,
            $e?->getMessage(),
            $e?->getTraceAsString(),
        ])));
    }
}
