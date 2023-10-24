<?php


namespace App\Services\MollieService;

use App\Events\MollieConnections\MollieConnectionCreated;
use App\Models\Organization;
use App\Services\MollieService\Exceptions\MollieApiException;
use App\Services\MollieService\Models\MollieConnection;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use League\OAuth2\Client\Grant\RefreshToken;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\BaseCollection;
use Mollie\Api\Resources\CurrentProfile;
use Mollie\Api\Resources\Method;
use Mollie\Api\Resources\MethodCollection;
use Mollie\Api\Resources\Onboarding;
use Mollie\Api\Resources\Payment;
use Mollie\Api\Resources\Profile;
use Mollie\Api\Resources\ProfileCollection;
use Mollie\Api\Resources\Refund;
use Mollie\OAuth2\Client\Provider\Mollie;

class MollieService
{
    protected bool $testMode = false;

    protected ?string $clientId;
    protected ?string $clientSecret;
    protected ?string $redirectUri;
    protected ?string $baseAccessToken;
    protected ?int $expireDecrease;
    protected ?MollieConnection $connection;
    protected \Psr\Log\LoggerInterface $logger;
    protected ?string $errorMessage;

    public const PAYMENT_METHOD_IDEAL = 'ideal';

    /**
     * @param MollieConnection|null $connection
     */
    public function __construct(?MollieConnection $connection = null)
    {
        $this->connection = $connection;
        $this->testMode = config('mollie.test_mode');
        $this->clientId = config('mollie.client_id');
        $this->clientSecret = config('mollie.client_secret');
        $this->redirectUri = config('mollie.redirect_url');
        $this->baseAccessToken = config('mollie.base_access_token');
        $this->expireDecrease = config('mollie.expire_decrease');

        $this->logger = Log::channel('mollie');
    }

    /**
     * @return Mollie
     */
    public function getProvider(): Mollie
    {
        return new Mollie([
            'clientId' => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'redirectUri' => $this->redirectUri
        ]);
    }

    /**
     * @param Organization $organization
     * @return string
     * @noinspection PhpUnused
     */
    public function mollieConnect(Organization $organization): string
    {
        $provider = $this->getProvider();
        $state = token_generator()->generate(64);

        $url = $provider->getAuthorizationUrl([
            'approval_prompt' => 'force',
            'state' => $state,
            'scope' => [
                \Mollie\OAuth2\Client\Provider\Mollie::SCOPE_ORGANIZATIONS_READ,
                \Mollie\OAuth2\Client\Provider\Mollie::SCOPE_PROFILES_READ,
                \Mollie\OAuth2\Client\Provider\Mollie::SCOPE_PROFILES_WRITE,
                \Mollie\OAuth2\Client\Provider\Mollie::SCOPE_PAYMENTS_READ,
                \Mollie\OAuth2\Client\Provider\Mollie::SCOPE_PAYMENTS_WRITE,
                \Mollie\OAuth2\Client\Provider\Mollie::SCOPE_ONBOARDING_READ,
                \Mollie\OAuth2\Client\Provider\Mollie::SCOPE_ONBOARDING_WRITE,
                \Mollie\OAuth2\Client\Provider\Mollie::SCOPE_REFUNDS_READ,
                \Mollie\OAuth2\Client\Provider\Mollie::SCOPE_REFUNDS_WRITE,
                \Mollie\OAuth2\Client\Provider\Mollie::SCOPE_BALANCES_READ,
            ],
        ]);

        MollieConnectionCreated::dispatch($organization->mollie_connections()->create([
            'state_code' => $state,
        ]));

        return $url;
    }

    /**
     * @param string $code
     * @param string $state
     * @return MollieConnection|null
     * @throws MollieApiException
     */
    public function exchangeOauthCode(string $code, string $state): ?MollieConnection
    {
        $provider = $this->getProvider();

        try {
            return $this->processConnectionByToken(
                $provider->getAccessToken('authorization_code', compact('code')),
                $state
            );
        } catch (IdentityProviderException $e) {
            return $this->processErrorResponse($e, 'exchangeOauthCode');
        }
    }

    /**
     * @param AccessToken $token
     * @param string $state
     * @return MollieConnection|null
     * @throws MollieApiException
     */
    private function processConnectionByToken(AccessToken $token, string $state): ?MollieConnection
    {
        $provider = $this->getProvider();
        $resourceOwner = $provider->getResourceOwner($token)->toArray();

        $connection = MollieConnection::where('state_code', $state)->first();

        return $connection?->updateConnectionByToken($token, $resourceOwner);
    }

    /**
     * @param string $refreshToken
     * @return AccessToken|AccessTokenInterface
     * @noinspection PhpUnused
     * @throws MollieApiException
     */
    public function refreshToken(string $refreshToken): AccessTokenInterface|AccessToken
    {
        try {
            $provider = $this->getProvider();
            $grant = new RefreshToken();

            return $provider->getAccessToken($grant, ['refresh_token' => $refreshToken]);
        } catch (IdentityProviderException $e) {
            return $this->processErrorResponse($e, 'refreshToken');
        }
    }

    /**
     * @param array $attributes
     * @return array
     * @throws MollieApiException
     * @throws \Exception
     */
    public function createClientLink(array $attributes = []): array
    {
        $mollie = $this->setAccessToken(new MollieApiClient(), $this->baseAccessToken);

        try {
            $response = $mollie->clientLinks->create([
                "name" => $attributes['name'],
                "owner" => [
                    "email" => $attributes['owner']['email'],
                    "givenName" => $attributes['owner']['first_name'],
                    "familyName" => $attributes['owner']['last_name'],
                    "locale" => "nl_NL",
                ],
                "address" => [
                    "streetAndNumber" => $attributes['address']['street'] ?? '',
                    "postalCode" => $attributes['address']['postcode'] ?? '',
                    "city" => $attributes['address']['city'] ?? '',
                    "country" => $attributes['address']['country_code'],
                ],
            ]);

            $state = token_generator()->generate(64);

            $url = $response->getRedirectUrl($this->clientId, $state, [
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
            ]);

            return compact('url', 'state');
        } catch (ApiException $e) {
            return $this->processErrorResponse($e, 'createClientLink');
        }
    }

    /**
     * @return \Mollie\Api\Resources\Organization
     * @noinspection PhpUnused
     * @throws MollieApiException
     */
    public function readOrganization(): \Mollie\Api\Resources\Organization
    {
        $mollie = $this->setAccessToken(new MollieApiClient());

        try {
            return $mollie->organizations->current();
        } catch (ApiException $e) {
            return $this->processErrorResponse($e, 'readOrganization');
        }
    }

    /**
     * @return \Mollie\Api\Resources\Organization
     * @noinspection PhpUnused
     * @throws MollieApiException
     */
    public function readOwnOrganization(): \Mollie\Api\Resources\Organization
    {
        $mollie = $this->setAccessToken(new MollieApiClient(), $this->baseAccessToken);

        try {
            return $mollie->organizations->current();
        } catch (ApiException $e) {
            return $this->processErrorResponse($e, 'readOwnOrganization');
        }
    }

    /**
     * @return Onboarding
     * @noinspection PhpUnused
     * @throws MollieApiException
     */
    public function readOnboardingState(): Onboarding
    {
        $mollie = $this->setAccessToken(new MollieApiClient());

        try {
            return $mollie->onboarding->get();
        } catch (ApiException $e) {
            return $this->processErrorResponse($e, 'readOnboardingState');
        }
    }

    /**
     * @param array $attributes
     * @return Profile
     * @noinspection PhpUnused
     * @throws MollieApiException
     */
    public function createProfile(array $attributes = []): Profile
    {
        $mollie = $this->setAccessToken(new MollieApiClient());

        try {
            return $mollie->profiles->create(array_merge(Arr::only($attributes, [
                'name', 'email', 'phone', 'website',
            ]), [
                'mode' => $this->testMode ? 'test' : 'live',
            ]));
        } catch (ApiException $e) {
            return $this->processErrorResponse($e, 'createProfile');
        }
    }

    /**
     * @param string $profileId
     * @return CurrentProfile|Profile
     * @noinspection PhpUnused
     * @throws MollieApiException
     */
    public function readProfile(string $profileId): Profile|CurrentProfile
    {
        $mollie = $this->setAccessToken(new MollieApiClient());

        try {
            return $mollie->profiles->get($profileId);
        } catch (ApiException $e) {
            return $this->processErrorResponse($e, 'readProfile');
        }
    }

    /**
     * @param string $profileId
     * @param array $attributes
     * @return Profile
     * @noinspection PhpUnused
     * @throws MollieApiException
     */
    public function updateProfile(string $profileId, array $attributes = []): Profile
    {
        $mollie = $this->setAccessToken(new MollieApiClient());

        try {
            return $mollie->profiles->update($profileId, $attributes);
        } catch (ApiException $e) {
            return $this->processErrorResponse($e, 'updateProfile');
        }
    }

    /**
     * @return ProfileCollection|null
     * @noinspection PhpUnused
     * @throws MollieApiException
     */
    public function readAllProfiles(): ?ProfileCollection
    {
        $mollie = $this->setAccessToken(new MollieApiClient());

        try {
            return $mollie->profiles->page();
        } catch (ApiException $e) {
            return $this->processErrorResponse($e, 'readAllProfiles');
        }
    }

    /**
     * @param string $profileId
     * @return BaseCollection|MethodCollection
     * @noinspection PhpUnused
     * @throws MollieApiException
     */
    public function readAllPaymentMethods(string $profileId): BaseCollection|MethodCollection
    {
        $mollie = $this->setAccessToken(new MollieApiClient());

        try {
            return $mollie->methods->allAvailable([
                'profileId' => $profileId,
                'testmode' => $this->testMode
            ]);
        } catch (ApiException $e) {
            return $this->processErrorResponse($e, 'readAllPaymentMethods');
        }
    }

    /**
     * @param string $profileId
     * @return BaseCollection|MethodCollection
     * @noinspection PhpUnused
     * @throws MollieApiException
     */
    public function readActivePaymentMethods(string $profileId): BaseCollection|MethodCollection
    {
        $mollie = $this->setAccessToken(new MollieApiClient());

        try {
            return $mollie->methods->allActive([
                'profileId' => $profileId,
                'testmode' => $this->testMode
            ]);
        } catch (ApiException $e) {
            return $this->processErrorResponse($e, 'readActivePaymentMethods');
        }
    }

    /**
     * @param string $profileId
     * @param string $method
     * @return Method
     * @noinspection PhpUnused
     * @throws MollieApiException
     */
    public function enablePaymentMethod(string $profileId, string $method): Method
    {
        $mollie = $this->setAccessToken(new MollieApiClient());

        try {
            return $mollie->profiles
                ->get($profileId)
                ->enableMethod($method);
        } catch (ApiException $e) {
            return $this->processErrorResponse($e, 'enablePaymentMethod');
        }
    }

    /**
     * @param string $profileId
     * @param string $method
     * @return Method
     * @noinspection PhpUnused
     * @throws MollieApiException
     */
    public function disablePaymentMethod(string $profileId, string $method): Method
    {
        $mollie = $this->setAccessToken(new MollieApiClient());

        try {
            return $mollie->profiles
                ->get($profileId)
                ->disableMethod($method);
        } catch (ApiException $e) {
            return $this->processErrorResponse($e, 'disablePaymentMethod');
        }
    }

    /**
     * @param string $profileId
     * @param array $attributes
     * @return string
     * @noinspection PhpUnused
     * @throws MollieApiException
     */
    public function createPayment(string $profileId, array $attributes): string
    {
        $mollie = $this->setAccessToken(new MollieApiClient());

        try {
            return $mollie->payments->create([
                'profileId' => $profileId,
                'amount' => [
                    "currency" => "EUR",
                    "value" => $attributes['amount'],
                ],
                'description' => $attributes['description'],
                'redirectUrl' => $attributes['redirect_url'] ?? url('mollie/success'),
                'cancelUrl' => $attributes['cancel_url'],
                'webhookUrl' => url('mollie/webhook'),
                'locale' => 'nl_NL',
                'method' => [self::PAYMENT_METHOD_IDEAL],
                'testmode' => $this->testMode,
            ])->getCheckoutUrl();
        } catch (ApiException $e) {
            return $this->processErrorResponse($e, 'createPayment');
        }
    }

    /**
     * @param string $paymentId
     * @return Payment
     * @noinspection PhpUnused
     * @throws MollieApiException
     */
    public function readPayment(string $paymentId): Payment
    {
        $mollie = $this->setAccessToken(new MollieApiClient());

        try {
            return $mollie->payments->get($paymentId, [
                'testmode' => $this->testMode,
            ]);
        } catch (ApiException $e) {
            return $this->processErrorResponse($e, 'readPayment');
        }
    }

    /**
     * @param string $paymentId
     * @param float|int $amount
     * @param string $description
     * @return Refund
     * @noinspection PhpUnused
     * @throws MollieApiException
     */
    public function refundPayment(
        string $paymentId,
        float|int $amount,
        string $description = ''
    ): Refund {
        $this->setAccessToken(new MollieApiClient());
        $payment = $this->readPayment($paymentId);

        try {
            return $payment->refund([
                'amount' => [
                    "currency" => "EUR",
                    "value" => $amount,
                ],
                'description' => $description,
                'testmode' => $this->testMode,
            ]);
        } catch (ApiException $e) {
            return $this->processErrorResponse($e, 'refundPayment');
        }
    }

    /**
     * @param string $paymentId
     * @param string $refundId
     * @return Refund
     * @noinspection PhpUnused
     * @throws MollieApiException
     */
    public function readPaymentRefund(string $paymentId, string $refundId): Refund
    {
        $this->setAccessToken(new MollieApiClient());
        $payment = $this->readPayment($paymentId);

        try {
            return $payment->getRefund($refundId, [
                'testmode' => $this->testMode,
            ]);
        } catch (ApiException $e) {
            return $this->processErrorResponse($e, 'readPaymentRefund');
        }
    }

    /**
     * @noinspection PhpUnused
     */
    public function revokeToken(): ?\Psr\Http\Message\ResponseInterface
    {
        $provider = $this->getProvider();

        try {
            return $provider->revokeRefreshToken($this->connection->active_token->remember_token);
        } catch (GuzzleException $e) {
            $this->logger->error("revokeToken fail. {$e->getMessage()}");

            return null;
        }
    }

    /**
     * @return string|null
     * @throws MollieApiException
     */
    public function getAccessToken(): ?string
    {
        if (!$this->connection) {
            return null;
        }

        if (($token = $this->connection->active_token) && !$token->isExpired()) {
            return $token->access_token;
        }

        if (!$token) {
            return null;
        }

        return $this->storeNewAccessToken($token->remember_token);
    }

    /**
     * @param string $remember_token
     * @return string
     * @throws MollieApiException
     */
    public function storeNewAccessToken(string $remember_token): string
    {
        $token = $this->refreshToken($remember_token);

        if ($this->connection) {
            $this->connection->tokens()->delete();
            $expiredAt = Carbon::createFromTimestamp($token->getExpires())
                ->subSeconds($this->expireDecrease);

            $this->connection->tokens()->create([
                'expired_at' => $expiredAt,
                'access_token' => $token->getToken(),
                'remember_token' => $token->getRefreshToken(),
            ]);

            $this->connection->refresh();
        }

        return $token->getToken();
    }

    /**
     * @param MollieApiClient $client
     * @param string|null $token
     * @return MollieApiClient
     * @throws MollieApiException
     */
    public function setAccessToken(MollieApiClient $client, string $token = null): MollieApiClient
    {
        try {
            return $client->setAccessToken($token ?? $this->getAccessToken());
        } catch (ApiException $e) {
            return $this->processErrorResponse($e, 'setAccessToken');
        }
    }

    /**
     * @param IdentityProviderException|ApiException $e
     * @param string $key
     * @throws MollieApiException
     */
    private function processErrorResponse(IdentityProviderException|ApiException $e, string $key)
    {
        $body = self::parseResponseBody(
            $e instanceof ApiException ? $e->getResponse() : $e->getResponseBody()
        );
        $this->logger->error("$key fail. {$e->getMessage()}");

        throw new MollieApiException(
            $body->detail ?? $body->title ?? 'Onbekende foutmelding!', $e->getCode()
        );
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return \stdClass|null
     */
    protected static function parseResponseBody(\Psr\Http\Message\ResponseInterface $response): ?\stdClass
    {
        $body = (string) $response->getBody();

        $object = @json_decode($body);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $object;
    }
}
