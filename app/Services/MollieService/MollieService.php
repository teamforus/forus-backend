<?php

namespace App\Services\MollieService;

use App\Services\MollieService\Exceptions\MollieException;
use App\Services\MollieService\Interfaces\MollieServiceInterface;
use App\Services\MollieService\Interfaces\MollieToken;
use App\Services\MollieService\Models\MollieConnection;
use App\Services\MollieService\Objects\Organization;
use App\Services\MollieService\Objects\Payment;
use App\Services\MollieService\Objects\PaymentMethod;
use App\Services\MollieService\Objects\Profile;
use App\Services\MollieService\Objects\Refund;
use App\Services\MollieService\Objects\ResourceOwner;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use League\OAuth2\Client\Grant\RefreshToken;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\Exceptions\RequestException;
use Mollie\Api\Http\Data\Money;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Method;
use Mollie\Api\Resources\Organization as MollieOrganization;
use Mollie\Api\Resources\Payment as MolliePayment;
use Mollie\Api\Resources\Profile as MollieProfile;
use Mollie\Api\Resources\Refund as MollieRefund;
use Mollie\OAuth2\Client\Provider\Mollie;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class MollieService implements MollieServiceInterface
{
    protected bool $testMode = false;

    protected ?string $appClientId;
    protected ?string $appClientSecret;
    protected ?string $redirectUri;
    protected ?string $webhookUri;

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
            'redirectUri' => $this->redirectUri,
        ]);
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
     * @throws MollieException
     * @return MollieConnection|null
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
     * @param string $refreshToken
     * @throws MollieException
     * @return AccessToken|AccessTokenInterface
     * @noinspection PhpUnused
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
     * @throws MollieException
     * @return string
     */
    public function createClientLink(
        string $state,
        string $name,
        array $owner = [],
        array $address = [],
    ): string {
        try {
            return $this->getMollie()->clientLinks->create([
                'name' => $name,
                'owner' => $owner,
                'address' => $address,
            ])->getRedirectUrl($this->appClientId, $state, $this->getRequiredScopes());
        } catch (ApiException $e) {
            $this->processApiException($e, 'createClientLink');
        } catch (Throwable $e) {
            $this->processException($e, 'createClientLink');
        }
    }

    /**
     * @throws RequestException|MollieException
     * @return Organization
     */
    public function getOrganization(): Organization
    {
        try {
            return $this->mapOrganization($this->getMollie()->organizations->current());
        } catch (ApiException $e) {
            $this->processApiException($e, 'readOrganization');
        }
    }

    /**
     * @throws MollieException
     * @return string
     * @noinspection PhpUnused
     */
    public function getOnboardingState(): string
    {
        return $this->getMollie()->onboarding->status()->status;
    }

    /**
     * @param array $attributes
     * @throws RequestException|MollieException
     * @return Profile
     * @noinspection PhpUnused
     */
    public function createProfile(array $attributes = []): Profile
    {
        try {
            return $this->mapProfile($this->getMollie()->profiles->create([
                ...Arr::only($attributes, ['name', 'email', 'phone', 'website']),
                'mode' => $this->testMode ? 'test' : 'live',
            ]));
        } catch (ApiException $e) {
            $this->processApiException($e, 'createProfile');
        }
    }

    /**
     * @param string $profileId
     * @throws RequestException|MollieException
     * @return Profile
     * @noinspection PhpUnused
     */
    public function readProfile(string $profileId): Profile
    {
        try {
            return $this->mapProfile($this->getMollie()->profiles->get($profileId));
        } catch (ApiException $e) {
            $this->processApiException($e, 'readProfile');
        }
    }

    /**
     * @param string $profileId
     * @param array $attributes
     * @throws RequestException|MollieException
     * @return Profile
     */
    public function updateProfile(string $profileId, array $attributes = []): Profile
    {
        try {
            return $this->mapProfile($this->getMollie()->profiles->update($profileId, $attributes));
        } catch (ApiException $e) {
            $this->processApiException($e, 'updateProfile');
        }
    }

    /**
     * @throws RequestException|MollieException
     * @return Collection
     * @noinspection PhpUnused
     */
    public function readAllProfiles(): Collection
    {
        try {
            return collect($this->getMollie()->profiles->page())
                ->map(fn (MollieProfile $profile) => $this->mapProfile($profile));
        } catch (ApiException $e) {
            $this->processApiException($e, 'readAllProfiles');
        }
    }

    /**
     * @param string $profileId
     * @throws RequestException|MollieException
     * @return Collection
     * @noinspection PhpUnused
     */
    public function readAllPaymentMethods(string $profileId): Collection
    {
        try {
            return collect($this->getMollie()->methods->all([
                'profileId' => $profileId,
                'testmode' => $this->testMode,
            ]))->map(fn (Method $method) => $this->mapPaymentMethod($method));
        } catch (ApiException $e) {
            $this->processApiException($e, 'readAllPaymentMethods');
        }
    }

    /**
     * @param string $profileId
     * @throws RequestException|MollieException
     * @return Collection
     * @noinspection PhpUnused
     */
    public function readActivePaymentMethods(string $profileId): Collection
    {
        try {
            return collect($this->getMollie()->methods->allEnabled([
                'profileId' => $profileId,
            ], testMode: $this->testMode))->map(fn (Method $method) => $this->mapPaymentMethod($method));
        } catch (ApiException $e) {
            $this->processApiException($e, 'readActivePaymentMethods');
        }
    }

    /**
     * @param string $profileId
     * @param string $method
     * @throws RequestException|MollieException
     * @return bool
     * @noinspection PhpUnused
     */
    public function enablePaymentMethod(string $profileId, string $method): bool
    {
        try {
            return (bool) $this->getMollie()->profiles->get($profileId)->enableMethod($method);
        } catch (ApiException $e) {
            $this->processApiException($e, 'enablePaymentMethod');
        }
    }

    /**
     * @param string $profileId
     * @param string $method
     * @throws MollieException
     * @throws RequestException
     * @return void
     */
    public function disablePaymentMethod(string $profileId, string $method): void
    {
        try {
            $this->getMollie()->profiles->get($profileId)->disableMethod($method);
        } catch (ApiException $e) {
            $this->processApiException($e, 'disablePaymentMethod');
        }
    }

    /**
     * @param string $profileId
     * @param array $attributes
     * @throws RequestException|MollieException
     * @return Payment
     */
    public function createPayment(string $profileId, array $attributes): Payment
    {
        try {
            return $this->mapPayment($this->getMollie()->payments->create([
                'profileId' => $profileId,
                'amount' => new Money($attributes['currency'], currency_format($attributes['amount'])),
                'description' => $attributes['description'],
                'redirectUrl' => $attributes['redirect_url'],
                'cancelUrl' => $attributes['cancel_url'],
                'webhookUrl' => $this->webhookUri,
                'locale' => 'nl_NL',
                'method' => self::PAYMENT_METHOD_IDEAL,
            ], testmode: $this->testMode));
        } catch (ApiException $e) {
            $this->processApiException($e, 'createPayment');
        }
    }

    /**
     * @param string $paymentId
     * @throws RequestException|MollieException
     * @return MolliePayment
     * @noinspection PhpUnused
     */
    public function getMolliePayment(string $paymentId): MolliePayment
    {
        try {
            return $this->getMollie()->payments->get($paymentId, testmode: $this->testMode);
        } catch (ApiException $e) {
            $this->processApiException($e, 'readPayment');
        }
    }

    /**
     * @param string $paymentId
     * @throws RequestException|MollieException
     * @return Payment
     * @noinspection PhpUnused
     */
    public function getPayment(string $paymentId): Payment
    {
        try {
            return $this->mapPayment($this->getMollie()->payments->get($paymentId, testmode: $this->testMode));
        } catch (ApiException $e) {
            $this->processApiException($e, 'readPayment');
        }
    }

    /**
     * @param string $paymentId
     * @throws MollieException|RequestException
     * @return Payment
     * @noinspection PhpUnused
     */
    public function cancelPayment(string $paymentId): Payment
    {
        try {
            return $this->mapPayment($this->getMollie()->payments->cancel($paymentId, testmode: $this->testMode));
        } catch (ApiException $e) {
            $this->processApiException($e, 'cancelPayment');
        }
    }

    /**
     * @param string $paymentId
     * @param array $attributes
     * @throws RequestException|MollieException
     * @return Refund
     * @noinspection PhpUnused
     */
    public function refundPayment(string $paymentId, array $attributes): Refund
    {
        try {
            $refund = $this->getMolliePayment($paymentId)->refund([
                'amount' => [
                    'currency' => $attributes['currency'],
                    'value' => currency_format($attributes['amount']),
                ],
                'description' => $attributes['description'],
                'testmode' => $this->testMode,
            ]);

            return $this->mapRefund($refund);
        } catch (ApiException $e) {
            $this->processApiException($e, 'refundPayment');
        }
    }

    /**
     * @param string $paymentId
     * @throws RequestException|MollieException
     * @return Collection
     */
    public function getPaymentRefunds(string $paymentId): Collection
    {
        try {
            return collect($this->getMolliePayment($paymentId)->refunds())
                ->map(fn (MollieRefund $refund) => $this->mapRefund($refund));
        } catch (ApiException $e) {
            $this->processApiException($e, 'readPaymentRefund');
        }
    }

    /**
     * @param string $paymentId
     * @param string $refundId
     * @throws RequestException|MollieException
     * @return Refund
     * @noinspection PhpUnused
     */
    public function readPaymentRefund(string $paymentId, string $refundId): Refund
    {
        try {
            return $this->mapRefund($this->getMolliePayment($paymentId)->getRefund($refundId, [
                'testmode' => $this->testMode,
            ]));
        } catch (ApiException $e) {
            $this->processApiException($e, 'readPaymentRefund');
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
            MollieServiceLogger::logError('Failed to revoke refresh token.', $e);

            return false;
        }
    }

    /**
     * @throws MollieException
     * @return string|null
     */
    public function getAccessToken(): ?string
    {
        if ($this->token->isTokenExpired()) {
            $this->token->setAccessToken($this->refreshToken($this->token->getRefreshToken()));
        }

        return $this->token->getAccessToken();
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
     * @throws MollieException
     */
    protected function getMollie(): MollieApiClient
    {
        try {
            return (new MollieApiClient())->setAccessToken($this->getAccessToken());
        } catch (Throwable $e) {
            MollieServiceLogger::logError('setAccessToken error.', $e);
            throw new MollieException('Unknown error please try later.', 503);
        }
    }

    /**
     * @param AccessToken $token
     * @param string $state
     * @return MollieConnection|null
     */
    private function processConnectionByToken(AccessToken $token, string $state): ?MollieConnection
    {
        $mollieConnections = MollieConnection::firstWhere('state_code', $state);

        return $mollieConnections?->updateConnectionByToken($token, $this->mapResourceOwner(
            $this->getProvider()->getResourceOwner($token)->toArray(),
        ));
    }

    /**
     * @param array $attributes
     * @return ResourceOwner
     */
    private function mapResourceOwner(array $attributes): ResourceOwner
    {
        return new ResourceOwner([
            'id' => $attributes['id'],
            'name' => $attributes['name'] ?? '',
            'city' => $attributes['address']['city'] ?? '',
            'street' => $attributes['address']['streetAndNumber'] ?? '',
            'country' => $attributes['address']['country'] ?? '',
            'postcode' => $attributes['address']['postalCode'] ?? '',
            'last_name' => $attributes['first_name'] ?? null,
            'first_name' => $attributes['last_name'] ?? null,
            'vat_number' => $attributes['vatNumber'] ?? null,
            'registration_number' => $attributes['registrationNumber'] ?? null,
        ]);
    }

    /**
     * @param MollieOrganization $organization
     * @return Organization
     */
    private function mapOrganization(MollieOrganization $organization): Organization
    {
        return new Organization([
            'id' => $organization->id,
            'name' => $organization->name,
            'email' => $organization->email,
            'city' => $organization->address->city ?? '',
            'street' => $organization->address->streetAndNumber ?? '',
            'country' => $organization->address->country ?? '',
            'postcode' => $organization->address->postalCode ?? '',
            'vat_number' => $organization->vatNumber ?? null,
            'registration_number' => $organization->registrationNumber ?? null,
        ]);
    }

    /**
     * @param MollieProfile $profile
     * @return Profile
     */
    private function mapProfile(MollieProfile $profile): Profile
    {
        return new Profile([
            'id' => $profile->id,
            'name' => $profile->name,
            'email' => $profile->email,
            'phone' => $profile->phone,
            'status' => $profile->status,
            'website' => $profile->website,
            'created_at' => $profile->createdAt ? Carbon::parse($profile->createdAt, 'UTC') : null,
        ]);
    }

    /**
     * @param Method $method
     * @return PaymentMethod
     */
    private function mapPaymentMethod(Method $method): PaymentMethod
    {
        return new PaymentMethod([
            'id' => $method->id,
            'status' => $method->status,
            'description' => $method->description,
        ]);
    }

    /**
     * @param MolliePayment $payment
     * @return Payment
     */
    private function mapPayment(MolliePayment $payment): Payment
    {
        return new Payment([
            'id' => $payment->id,
            'amount' => $payment->amount->value,
            'status' => $payment->status,
            'method' => $payment->method,
            'paid_at' => $payment->paidAt ? Carbon::parse($payment->paidAt, 'UTC') : null,
            'currency' => $payment->amount->currency,
            'profile_id' => $payment->profileId,
            'canceled_at' => $payment->canceledAt ? Carbon::parse($payment->canceledAt, 'UTC') : null,
            'expires_at' => $payment->expiresAt ? Carbon::parse($payment->expiresAt, 'UTC') : null,
            'expired_at' => $payment->expiredAt ? Carbon::parse($payment->expiredAt, 'UTC') : null,
            'created_at' => $payment->createdAt ? Carbon::parse($payment->createdAt, 'UTC') : null,
            'description' => $payment->description,
            'checkout_url' => $payment->getCheckoutUrl(),
            'amount_refunded' => $payment->amountRefunded?->value,
            'amount_captured' => $payment->amountCaptured?->value,
            'amount_remaining' => $payment->amountRemaining?->value,
        ]);
    }

    /**
     * @param MollieRefund $refund
     * @return Refund
     */
    private function mapRefund(MollieRefund $refund): Refund
    {
        return new Refund([
            'id' => $refund->id,
            'status' => $refund->status,
            'amount' => $refund->amount->value,
            'currency' => $refund->amount->currency,
            'payment_id' => $refund->paymentId,
            'created_at' => $refund->createdAt ? Carbon::parse($refund->createdAt, 'UTC') : null,
            'description' => $refund->description,
        ]);
    }

    /**
     * @param ApiException $e
     * @param string $key
     * @throws MollieException
     * @return void
     */
    private function processApiException(ApiException $e, string $key): void
    {
        $body = static::parseResponseBody($e->getResponse()->getPsrResponse());

        try {
            MollieServiceLogger::logError("$key api error.\n" . json_encode($body, JSON_THROW_ON_ERROR), $e);
        } catch (Throwable) {
        }

        throw new MollieException(
            Arr::get($body, 'detail', Arr::get($body, 'title', 'Onbekende foutmelding!')),
            $e->getCode(),
        );
    }

    /**
     * @param ResponseInterface|null $response
     * @throws MollieException
     * @return array|null
     */
    private static function parseResponseBody(?ResponseInterface $response): ?array
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
     * @param ApiException $e
     * @param string $key
     * @throws MollieException
     * @return void
     */
    private function processException(Throwable $e, string $key): void
    {
        try {
            MollieServiceLogger::logError("$key error.\n", $e);
        } catch (Throwable) {
        }

        throw new MollieException($e->getMessage(), $e->getCode());
    }
}
