<?php

namespace App\Services\MollieService\Models;

use App\Events\MollieConnections\MollieConnectionCompleted;
use App\Events\MollieConnections\MollieConnectionCreated;
use App\Events\MollieConnections\MollieConnectionDeleted;
use App\Events\MollieConnections\MollieConnectionUpdated;
use App\Models\Employee;
use App\Models\Organization;
use App\Services\EventLogService\Traits\HasLogs;
use App\Services\MollieService\Exceptions\MollieApiException;
use App\Services\MollieService\MollieService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use League\OAuth2\Client\Token\AccessToken;
use Mollie\Api\Resources\Payment;
use Mollie\Api\Resources\Profile;

/**
 * App\Services\MollieService\Models\MollieConnection
 *
 * @property int $id
 * @property string|null $mollie_organization_id
 * @property string|null $organization_name
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $country
 * @property string|null $city
 * @property string|null $street
 * @property string|null $postcode
 * @property string|null $state_code
 * @property string|null $vat_number
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property string|null $business_type
 * @property string|null $registration_number
 * @property string $connection_state
 * @property string $onboarding_state
 * @property int $organization_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Services\MollieService\Models\MollieConnectionProfile|null $active_profile
 * @property-read \App\Services\MollieService\Models\MollieConnectionToken|null $active_token
 * @property-read string $onboarding_state_locale
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\EventLogService\Models\EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read Organization $organization
 * @property-read \App\Services\MollieService\Models\MollieConnectionProfile|null $pending_profile
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\MollieService\Models\MollieConnectionProfile[] $profiles
 * @property-read int|null $profiles_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\MollieService\Models\MollieConnectionToken[] $tokens
 * @property-read int|null $tokens_count
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnection newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnection newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnection onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnection query()
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnection whereBusinessType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnection whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnection whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnection whereConnectionState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnection whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnection whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnection whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnection whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnection whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnection whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnection whereMollieOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnection whereOnboardingState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnection whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnection whereOrganizationName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnection wherePostcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnection whereRegistrationNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnection whereStateCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnection whereStreet($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnection whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnection whereVatNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnection withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnection withoutTrashed()
 * @mixin \Eloquent
 */
class MollieConnection extends Model
{
    use SoftDeletes, HasLogs;

    public const CONNECTION_STATE_ACTIVE = 'active';
    public const CONNECTION_STATE_PENDING = 'pending';

    public const ONBOARDING_STATE_COMPLETED = 'completed';
    public const ONBOARDING_STATE_PENDING = 'needs-data';
    public const ONBOARDING_STATE_IN_REVIEW = 'in-review';

    public const ONBOARDING_STATES = [
        self::ONBOARDING_STATE_COMPLETED,
        self::ONBOARDING_STATE_PENDING,
        self::ONBOARDING_STATE_IN_REVIEW,
    ];

    public const PENDING_ONBOARDING_STATES = [
        self::ONBOARDING_STATE_PENDING,
        self::ONBOARDING_STATE_IN_REVIEW,
    ];

    public const EVENT_CREATED = 'created';
    public const EVENT_UPDATED = 'updated';
    public const EVENT_COMPLETED = 'completed';
    public const EVENT_DELETED = 'deleted';

    /**
     * @var string[]
     */
    protected $fillable = [
        'city',
        'street',
        'country',
        'postcode',
        'last_name',
        'first_name',
        'state_code',
        'vat_number',
        'completed_at',
        'business_type',
        'organization_id',
        'connection_state',
        'onboarding_state',
        'organization_name',
        'registration_number',
        'mollie_organization_id',
    ];

    /**
     * @var string[]
     */
    protected $dates = [
        'completed_at',
    ];

    /**
     * @noinspection PhpUnused
     * @return BelongsTo
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @noinspection PhpUnused
     * @return HasMany
     */
    public function profiles(): HasMany
    {
        return $this->hasMany(MollieConnectionProfile::class);
    }

    /**
     * @noinspection PhpUnused
     * @return HasOne
     */
    public function active_profile(): HasOne
    {
        return $this->hasOne(MollieConnectionProfile::class)
            ->where('state', MollieConnectionProfile::STATE_ACTIVE)
            ->where('current', true);
    }

    /**
     * @noinspection PhpUnused
     * @return HasOne
     */
    public function pending_profile(): HasOne
    {
        return $this->hasOne(MollieConnectionProfile::class)
            ->where('state', MollieConnectionProfile::STATE_PENDING)
            ->where('current', true);
    }

    /**
     * @noinspection PhpUnused
     * @return HasMany
     */
    public function tokens(): HasMany
    {
        /** @var HasMany|SoftDeletes $hasMany */
        $hasMany = $this->hasMany(MollieConnectionToken::class);

        return $hasMany->withTrashed();
    }

    /**
     * @noinspection PhpUnused
     * @return HasOne
     */
    public function active_token(): HasOne
    {
        return $this->hasOne(MollieConnectionToken::class)->latest();
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getOnboardingStateLocaleAttribute(): string
    {
        return trans('states/mollie_connection.' . $this->onboarding_state);
    }

    /**
     * @param Employee|null $employee
     * @param array $extraModels
     * @return array
     */
    public function getLogModels(?Employee $employee = null, array $extraModels = []): array
    {
        return array_merge([
            'employee' => $employee,
            'organization' => $this->organization,
            'mollie_connection' => $this,
        ], $extraModels);
    }

    /**
     * @param MollieConnectionProfile $profile
     * @return MollieConnectionProfile
     * @throws MollieApiException
     */
    public function createProfile(MollieConnectionProfile $profile): MollieConnectionProfile
    {
        $profileResponse = MollieService::make($this)->createProfile($profile->only([
            'name', 'email', 'phone', 'website',
        ]));

        return $this->updateProfileModelFromApiResponse($profile, $profileResponse);
    }

    /**
     * @param MollieConnectionProfile $profile
     * @return MollieConnectionProfile
     * @throws MollieApiException
     */
    public function updateProfile(MollieConnectionProfile $profile): MollieConnectionProfile
    {
        $profileResponse = MollieService::make($this)->updateProfile($profile->mollie_id, $profile->only([
            'name', 'email', 'phone', 'website',
        ]));

        return $this->updateProfileModelFromApiResponse($profile, $profileResponse);
    }

    /**
     * @param MollieConnectionProfile $profile
     * @param Profile $profileResponse
     * @return MollieConnectionProfile
     * @throws MollieApiException
     */
    private function updateProfileModelFromApiResponse(
        MollieConnectionProfile $profile,
        Profile $profileResponse
    ): MollieConnectionProfile {
        $profile->mollie_connection->profiles()->where('id', '!=', $profile->id)->update([
            'current' => false
        ]);

        $profile->update([
            'mollie_id' => $profileResponse->id,
            'name' => $profileResponse->name,
            'email' => $profileResponse->email,
            'phone' => $profileResponse->phone,
            'website' => $profileResponse->website,
            'state' => MollieConnectionProfile::STATE_ACTIVE,
            'current' => true,
        ]);

        $service = MollieService::make($this);
        $service->enablePaymentMethod($profileResponse->id, $service::PAYMENT_METHOD_IDEAL);

        return $profile;
    }

    /**
     * @return MollieConnection
     * @throws MollieApiException
     */
    public function fetchAndUpdateConnection(): MollieConnection
    {
        $service = MollieService::make($this);

        $state = $service->readOnboardingState();
        $oldState = $this->onboarding_state;
        $organization = $service->readOrganization();

        $onboardingComplete = $oldState !== $state->status &&
            $state->status === self::ONBOARDING_STATE_COMPLETED;

        $this->update([
            'city' => $organization->address->city ?? '',
            'street' => $organization->address->streetAndNumber ?? '',
            'country' => $organization->address->country ?? '',
            'postcode' => $organization->address->postalCode ?? '',
            'organization_name' => $organization->name,
            'mollie_organization_id' => $organization->id,
            'completed_at' => $onboardingComplete ? now() : $this->completed_at,
            'onboarding_state' => in_array($state->status, self::ONBOARDING_STATES)
                ? $state->status
                : $this->onboarding_state
        ]);

        try {
            $this->updateProfiles();
        } catch (MollieApiException $e) {
            MollieService::logError("Update profile error in fetchAndUpdateConnection", $e);
        }

        MollieConnectionUpdated::dispatch($this);

        if ($onboardingComplete) {
            MollieConnectionCompleted::dispatch($this);
        }

        return $this->refresh();
    }

    /**
     * @param bool $createIfEmpty
     * @return void
     * @throws MollieApiException
     */
    private function updateProfiles(bool $createIfEmpty = false): void
    {
        $service = MollieService::make($this);
        $profiles = $service->readAllProfiles();

        if ($createIfEmpty && $this->pending_profile) {
            $this->createProfile($this->pending_profile);
        }

        /** @var Profile $profile */
        foreach ($profiles as $profile) {
            $this->profiles()->updateOrCreate([
                'mollie_id' => $profile->id,
            ], [
                'name' => $profile->name,
                'email' => $profile->email,
                'phone' => $profile->phone,
                'website' => $profile->website,
                'state' => MollieConnectionProfile::STATE_ACTIVE,
            ]);

            if ($this->onboarding_state === self::ONBOARDING_STATE_COMPLETED) {
                $service->enablePaymentMethod($profile->id, $service::PAYMENT_METHOD_IDEAL);
            }
        }

        if (!$this->active_profile()->exists()) {
            $this->profiles()
                ->where('state', MollieConnectionProfile::STATE_ACTIVE)
                ->take(1)
                ->update([
                    'current' => true
                ]);
        }
    }

    /**
     * @param AccessToken $token
     * @param array $attributes
     * @return MollieConnection
     */
    public function updateConnectionByToken(AccessToken $token, array $attributes): MollieConnection
    {
        $this->deleteAlreadyConfiguredConnections();

        $this->update([
            'connection_state' => self::CONNECTION_STATE_ACTIVE,
            'city' => $attributes['address']['city'] ?? '',
            'street' => $attributes['address']['streetAndNumber'] ?? '',
            'country' => $attributes['address']['country'] ?? '',
            'postcode' => $attributes['address']['postalCode'] ?? '',
            'last_name' => $attributes['first_name'] ?? $this->last_name,
            'first_name' => $attributes['last_name'] ?? $this->first_name,
            'state_code' => null,
            'organization_name' => $attributes['name'] ?? '',
            'mollie_organization_id' => $attributes['id'],
        ]);

        $this->tokens()->delete();
        $expiredAt = Carbon::createFromTimestamp($token->getExpires())
            ->subSeconds(config('mollie.expire_decrease'));

        $this->tokens()->create([
            'expired_at' => $expiredAt,
            'access_token' => $token->getToken(),
            'remember_token' => $token->getRefreshToken(),
        ]);

        $this->refresh();

        try {
            $this->updateProfiles(true);
        } catch (MollieApiException $e) {
            MollieService::logError("Update profile error in updateConnectionByToken", $e);
        }

        MollieConnectionUpdated::dispatch($this);

        return $this;
    }

    /**
     * @return void
     */
    private function deleteAlreadyConfiguredConnections(): void
    {
        self::where('organization_id', $this->organization_id)
            ->where('id', '!=', $this->id)
            ->where('connection_state', self::CONNECTION_STATE_ACTIVE)
            ->get()
            ->each(function (MollieConnection $connection) {
                $connection->delete();
            });
    }

    /**
     * @param Organization $organization
     * @param array $attributes
     * @return MollieConnection
     */
    public static function makeNewConnection(Organization $organization, array $attributes): MollieConnection
    {
        $organization->mollie_connections->each(function (MollieConnection $mollieConnection) {
            MollieConnectionDeleted::dispatch($mollieConnection);
            $mollieConnection->delete();
        });

        /** @var MollieConnection $connection */
        $connection = $organization->mollie_connections()->create(Arr::only($attributes, [
            'name', 'email', 'first_name', 'last_name', 'street', 'city', 'postcode', 'state_code',
        ]));

        $connection->profiles()->create(array_merge($attributes['profile'], [
            'current' => true
        ]));

        MollieConnectionCreated::dispatch($connection);

        return $connection;
    }

    /**
     * @param string $paymentId
     * @return Payment|null
     */
    public function cancelPayment(string $paymentId): ?Payment
    {
        $service = new MollieService($this);

        try {
            $payment = $service->readPayment($paymentId);

            return $payment->isCancelable ? $service->cancelPayment($paymentId) : null;
        } catch (MollieApiException $e) {
            return null;
        }
    }

    /**
     * @param array $attributes
     * @return Payment|null
     */
    public function createPayment(array $attributes): ?Payment
    {
        if (!($profile = $this->active_profile)) {
            return null;
        }

        $service = new MollieService($this);

        try {
            return $service->createPayment($profile->mollie_id, Arr::only($attributes, [
                'amount', 'currency', 'description', 'redirect_url', 'cancel_url',
            ]));
        } catch (MollieApiException $e) {
            return null;
        }
    }

    /**
     * @param string $paymentId
     * @return Payment|null
     */
    public function readPayment(string $paymentId): ?Payment
    {
        $service = new MollieService($this);

        try {
            return $service->readPayment($paymentId);
        } catch (MollieApiException $e) {
            return null;
        }
    }
}