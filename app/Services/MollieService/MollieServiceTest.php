<?php


namespace App\Services\MollieService;

use App\Models\ReservationExtraPayment;
use App\Models\ReservationExtraPaymentRefund;
use App\Services\MollieService\Interfaces\MollieServiceInterface;
use App\Services\MollieService\Interfaces\MollieToken;
use App\Services\MollieService\Models\MollieConnection;
use App\Services\MollieService\Objects\Organization;
use App\Services\MollieService\Objects\Payment;
use App\Services\MollieService\Objects\PaymentMethod;
use App\Services\MollieService\Objects\Profile;
use App\Services\MollieService\Objects\Refund;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class MollieServiceTest implements MollieServiceInterface
{
    /** @var array */
    protected array $data;

    /**
     * @param MollieToken|null $token
     */
    protected function __construct(protected ?MollieToken $token = null)
    {
        $this->data = Arr::map(Config::get('mollie.test_data'), fn(mixed $item, string $key) => match($key) {
            'connection' => $this->mapTestConnectionData($item),
            'payment' => $this->mapTestPaymentData($item),
            'refund' => $item ? $this->mapTestRefundData($item) : null,
            default => null,
        });
    }

    /**
     * @param array $data
     * @return array
     */
    private function mapTestConnectionData(array $data): array
    {
        return [
            ...$data,
            'profile' => [
                ...$data['profile'],
                'created_at' => now()
            ],
        ];
    }

    /**
     * @param array $data
     * @return array
     */
    private function mapTestPaymentData(array $data): array
    {
        return [
            ...$data,
            'paid_at' => now(),
            'created_at' => now(),
            'expires_at' => now()->addMinutes(15),
        ];
    }

    /**
     * @param array $data
     * @return array
     */
    private function mapTestRefundData(array $data): array
    {
        return [
            ...$data,
            'created_at' => now(),
        ];
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
     * @param string $state
     * @return string
     */
    public function mollieConnect(string $state): string
    {
        return url('');
    }

    /**
     * @param string $code
     * @param string $state
     * @return MollieConnection|null
     */
    public function exchangeOauthCode(string $code, string $state): ?MollieConnection
    {
        $connection = MollieConnection::create([
            ...Arr::except($this->data['connection']['organization'], 'id'),
            'completed_at' => now(),
            'organization_id' => $this->data['connection']['organization_id'],
            'connection_state' => MollieConnection::STATE_ACTIVE,
            'mollie_organization_id' => $this->data['connection']['organization']['id'],
        ]);

        $connection->profiles()->create($this->data['connection']['profile']);

        return $connection;
    }

    /**
     * @param string $state
     * @param string $name
     * @param array $owner
     * @param array $address
     * @return string
     */
    public function createClientLink(
        string $state,
        string $name,
        array $owner = [],
        array $address = [],
    ): string {
        return url('');
    }

    /**
     * @return Organization
     */
    public function getOrganization(): Organization
    {
        return new Organization($this->data['connection']['organization']);
    }

    /**
     * @return string
     */
    public function getOnboardingState(): string
    {
        return 'completed';
    }

    /**
     * @param array $attributes
     * @return Profile
     */
    public function createProfile(array $attributes = []): Profile
    {
        return new Profile([
            'id' => 'profile_' . Str::random(5),
            ...Arr::only($attributes, [
                'name', 'email', 'phone', 'website',
            ]),
        ]);
    }

    /**
     * @param string $profileId
     * @return Profile
     */
    public function readProfile(string $profileId): Profile
    {
        return new Profile($this->data['connection']['profile']);
    }

    /**
     * @param string $profileId
     * @param array $attributes
     * @return Profile
     */
    public function updateProfile(string $profileId, array $attributes = []): Profile
    {
        return new Profile($this->data['connection']['profile']);
    }

    /**
     * @return Collection
     */
    public function readAllProfiles(): Collection
    {
        return collect([
            new Profile($this->data['connection']['profile'])
        ]);
    }

    /**
     * @param string $profileId
     * @return Collection
     */
    public function readAllPaymentMethods(string $profileId): Collection
    {
        return collect([
            new PaymentMethod($this->data['connection']['payment_method'])
        ]);
    }

    /**
     * @param string $profileId
     * @return Collection
     */
    public function readActivePaymentMethods(string $profileId): Collection
    {
        return collect([
            new PaymentMethod($this->data['connection']['payment_method'])
        ]);
    }

    /**
     * @param string $profileId
     * @param string $method
     * @return bool
     */
    public function enablePaymentMethod(string $profileId, string $method): bool
    {
        return true;
    }

    /**
     * @param string $profileId
     * @param string $method
     * @return bool
     */
    public function disablePaymentMethod(string $profileId, string $method): bool
    {
        return true;
    }

    /**
     * @param string $profileId
     * @param array $attributes
     * @return Payment
     */
    public function createPayment(string $profileId, array $attributes): Payment
    {
        return new Payment($this->mapTestPaymentData([
            'id' => 'payment_' . Str::random(5),
            'status' => ReservationExtraPayment::STATE_OPEN,
            'method' => self::PAYMENT_METHOD_IDEAL,
            'profile_id' => $profileId,
            'checkout_url' => $attributes['redirect_url'],
            ...$attributes
        ]));
    }

    /**
     * @param string $paymentId
     * @return Payment
     * @noinspection PhpUnused
     */
    public function getPayment(string $paymentId): Payment
    {
        return new Payment($this->data['payment']);
    }

    /**
     * @param string $paymentId
     * @return Payment
     * @noinspection PhpUnused
     */
    public function cancelPayment(string $paymentId): Payment
    {
        return new Payment($this->data['payment']);
    }

    /**
     * @param string $paymentId
     * @param array $attributes
     * @return Refund
     * @noinspection PhpUnused
     */
    public function refundPayment(string $paymentId, array $attributes): Refund
    {
        return new Refund($this->mapTestRefundData([
            'id' => 'refund_' . Str::random(5),
            'payment_id' => $paymentId,
            'status' => $this->data['refund']['status'] ?? ReservationExtraPaymentRefund::STATE_REFUNDED,
            ...$attributes
        ]));
    }

    /**
     * @param string $paymentId
     * @return Collection
     */
    public function getPaymentRefunds(string $paymentId): Collection
    {
        return collect($this->data['refund'] ? [
            new Refund($this->data['refund'])
        ] : []);
    }

    /**
     * @return bool
     */
    public function revokeToken(): bool
    {
        return true;
    }
}
