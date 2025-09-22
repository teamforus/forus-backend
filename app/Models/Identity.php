<?php

namespace App\Models;

use App\Http\Requests\BaseFormRequest;
use App\Scopes\Builders\IdentityQuery;
use App\Services\Forus\Auth2FAService\Auth2FAService;
use App\Services\Forus\Auth2FAService\Data\Auth2FASecret;
use App\Services\Forus\Auth2FAService\Models\Auth2FAProvider;
use App\Services\Forus\Notification\Models\NotificationToken;
use App\Services\Forus\Session\Models\Session;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use Throwable;

/**
 * App\Models\Identity.
 *
 * @property int $id
 * @property string $pin_code
 * @property string $public_key
 * @property string $private_key
 * @property string|null $passphrase
 * @property string $address
 * @property int|null $creator_organization_id
 * @property int|null $creator_employee_id
 * @property string|null $type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $auth_2fa_remember_ip
 * @property-read Collection|Auth2FAProvider[] $auth_2fa_providers_active
 * @property-read int|null $auth_2fa_providers_active_count
 * @property-read \App\Models\Employee|null $creator_employee
 * @property-read Collection|\App\Models\IdentityEmail[] $emails
 * @property-read int|null $emails_count
 * @property-read Collection|\App\Models\IdentityEmail[] $emails_verified
 * @property-read int|null $emails_verified_count
 * @property-read Collection|\App\Models\Employee[] $employees
 * @property-read int|null $employees_count
 * @property-read Collection|\App\Models\FundRequest[] $fund_requests
 * @property-read int|null $fund_requests_count
 * @property-read Collection|\App\Models\Fund[] $funds
 * @property-read int|null $funds_count
 * @property-read string|null $bsn
 * @property-read string|null $email
 * @property-read string|null $type_locale
 * @property-read Collection|\App\Models\Identity2FA[] $identity_2fa
 * @property-read int|null $identity_2fa_count
 * @property-read Collection|\App\Models\Identity2FA[] $identity_2fa_active
 * @property-read int|null $identity_2fa_active_count
 * @property-read \App\Models\IdentityEmail|null $initial_email
 * @property-read Collection|NotificationToken[] $notification_tokens
 * @property-read int|null $notification_tokens_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\App\Models\Notification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection|\App\Models\Organization[] $organizations
 * @property-read int|null $organizations_count
 * @property-read Collection|\App\Models\PhysicalCard[] $physical_cards
 * @property-read int|null $physical_cards_count
 * @property-read \App\Models\IdentityEmail|null $primary_email
 * @property-read Collection|\App\Models\ProductReservation[] $product_reservations
 * @property-read int|null $product_reservations_count
 * @property-read Collection|\App\Models\Profile[] $profiles
 * @property-read int|null $profiles_count
 * @property-read Collection|\App\Models\IdentityProxy[] $proxies
 * @property-read int|null $proxies_count
 * @property-read \App\Models\Record|null $record_bsn
 * @property-read Collection|\App\Models\RecordCategory[] $record_categories
 * @property-read int|null $record_categories_count
 * @property-read Collection|\App\Models\Record[] $records
 * @property-read int|null $records_count
 * @property-read Collection|\App\Models\Reimbursement[] $reimbursements
 * @property-read int|null $reimbursements_count
 * @property-read Session|null $session_last_activity
 * @property-read Session|null $session_last_login
 * @property-read Collection|Session[] $sessions
 * @property-read int|null $sessions_count
 * @property-read Collection|\App\Models\Voucher[] $vouchers
 * @property-read int|null $vouchers_count
 * @method static Builder<static>|Identity newModelQuery()
 * @method static Builder<static>|Identity newQuery()
 * @method static Builder<static>|Identity query()
 * @method static Builder<static>|Identity whereAddress($value)
 * @method static Builder<static>|Identity whereAuth2faRememberIp($value)
 * @method static Builder<static>|Identity whereCreatedAt($value)
 * @method static Builder<static>|Identity whereCreatorEmployeeId($value)
 * @method static Builder<static>|Identity whereCreatorOrganizationId($value)
 * @method static Builder<static>|Identity whereId($value)
 * @method static Builder<static>|Identity wherePassphrase($value)
 * @method static Builder<static>|Identity wherePinCode($value)
 * @method static Builder<static>|Identity wherePrivateKey($value)
 * @method static Builder<static>|Identity wherePublicKey($value)
 * @method static Builder<static>|Identity whereType($value)
 * @method static Builder<static>|Identity whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Identity extends Model implements Authenticatable
{
    use Notifiable;

    public const string TYPE_VOUCHER = 'voucher';
    public const string TYPE_PROFILE = 'profile';
    public const string TYPE_EMPLOYEE = 'employee';

    public const array TYPES = [
        self::TYPE_VOUCHER,
        self::TYPE_PROFILE,
        self::TYPE_EMPLOYEE,
    ];

    /**
     * How much time a user has to exchange their exchange_token.
     * @var array
     */
    public const array EXPIRATION_TIMES = [
        // 1 minute
        'short_token' => 60,
        // 10 minutes
        'pin_code' => 60 * 10,
        // 60 minutes
        'qr_code' => 60 * 60,
        // 60 minutes
        'email_code' => 60 * 60,
        // 1 month
        'confirmation_code' => 60 * 60 * 24 * 30,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'pin_code', 'address', 'passphrase', 'private_key', 'public_key', 'auth_2fa_remember_ip', 'type',
        'creator_organization_id', 'creator_employee_id',
    ];

    /**
     * @var string[]
     */
    protected $hidden = [
        'pin_code', 'passphrase', 'private_key', 'public_key', 'auth_2fa_remember_ip',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'auth_2fa_remember_ip' => 'boolean',
    ];

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->address ?: '';
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function emails(): HasMany
    {
        return $this->hasMany(IdentityEmail::class, 'identity_address', 'address');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function emails_verified(): HasMany
    {
        return $this
            ->hasMany(IdentityEmail::class, 'identity_address', 'address')
            ->where('verified', true);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function profiles(): HasMany
    {
        return $this->hasMany(Profile::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class, 'identity_address', 'address');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function creator_employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'creator_employee_id');
    }

    /**
     * @return HasMany
     */
    public function notification_tokens(): HasMany
    {
        return $this->hasMany(NotificationToken::class, 'identity_address', 'address');
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function identity_2fa(): HasMany
    {
        return $this->hasMany(Identity2FA::class, 'identity_address', 'address');
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function identity_2fa_active(): HasMany
    {
        return $this
            ->hasMany(Identity2FA::class, 'identity_address', 'address')
            ->where('state', Identity2FA::STATE_ACTIVE);
    }

    /**
     * @return BelongsToMany
     * @noinspection PhpUnused
     */
    public function auth_2fa_providers_active(): BelongsToMany
    {
        return $this->auth_2fa_providers()->where([
            'identity_2fa.state' => Identity2FA::STATE_ACTIVE,
        ])->whereNull('deleted_at');
    }

    /**
     * @return BelongsToMany
     * @noinspection PhpUnused
     */
    public function funds(): BelongsToMany
    {
        return $this->belongsToMany(
            Fund::class,
            'vouchers',
            'identity_id',
            'fund_id',
            'id',
        )->groupBy('funds.id');
    }

    /**
     * @return Identity|null
     */
    public static function auth(): ?Identity
    {
        return BaseFormRequest::createFrom(request())->identity();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     * @noinspection PhpUnused
     */
    public function primary_email(): HasOne
    {
        return $this->hasOne(IdentityEmail::class, 'identity_address', 'address')->where([
            'primary' => true,
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     * @noinspection PhpUnused
     */
    public function initial_email(): HasOne
    {
        return $this->hasOne(IdentityEmail::class, 'identity_address', 'address')->where([
            'initial' => true,
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function proxies(): HasMany
    {
        return $this->hasMany(IdentityProxy::class, 'identity_address', 'address');
    }

    /**
     * @return HasManyThrough
     */
    public function sessions(): HasManyThrough
    {
        return $this->hasManyThrough(
            Session::class,
            IdentityProxy::class,
            'identity_address',
            'identity_proxy_id',
            'address',
            'id',
        );
    }

    /**
     * @return HasOneThrough
     * @noinspection PhpUnused
     */
    public function session_last_activity(): HasOneThrough
    {
        return $this->hasOneThrough(
            Session::class,
            IdentityProxy::class,
            'identity_address',
            'identity_proxy_id',
            'address',
            'id',
        )->latest('last_activity_at');
    }

    /**
     * @return HasOneThrough
     * @noinspection PhpUnused
     */
    public function session_last_login(): HasOneThrough
    {
        return $this->hasOneThrough(
            Session::class,
            IdentityProxy::class,
            'identity_address',
            'identity_proxy_id',
            'address',
            'id',
        )->latest('created_at');
    }

    /**
     * @return HasMany
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'identity_address', 'address');
    }

    /**
     * Get the entity's notifications.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable')
            ->orderBy('created_at', 'desc');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function physical_cards(): HasMany
    {
        return $this->hasMany(PhysicalCard::class, 'identity_address', 'address');
    }

    /**
     * @return HasMany
     */
    public function records(): HasMany
    {
        return $this->hasMany(Record::class, 'identity_address', 'address');
    }

    /**
     * @return HasMany
     */
    public function fund_requests(): HasMany
    {
        return $this->hasMany(FundRequest::class);
    }

    /**
     * @return HasManyThrough
     */
    public function reimbursements(): HasManyThrough
    {
        return $this->hasManyThrough(
            Reimbursement::class,
            Voucher::class,
            'identity_id',
            'voucher_id',
            'id',
            'id',
        );
    }

    /**
     * @return HasManyThrough
     */
    public function product_reservations(): HasManyThrough
    {
        return $this->hasManyThrough(
            ProductReservation::class,
            Voucher::class,
            'identity_id',
            'voucher_id',
            'id',
            'id',
        );
    }

    /**
     * @return HasOne
     * @noinspection PhpUnused
     */
    public function record_bsn(): HasOne
    {
        return $this
            ->hasOne(Record::class, 'identity_address', 'address')
            ->whereRelation('record_type', 'key', 'bsn')
            ->latest();
    }

    /**
     * @return HasMany
     */
    public function record_categories(): HasMany
    {
        return $this->hasMany(RecordCategory::class, 'identity_address', 'address');
    }

    /**
     * @return string
     */
    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    /**
     * @param string|null $email
     * @return Identity|BaseModel|null
     */
    public static function findByEmail(?string $email): Identity|Model|null
    {
        return static::whereRelation('primary_email', 'email', '=', $email)->first();
    }

    /**
     * @param string|null $bsn
     * @return Identity|BaseModel|null
     */
    public static function findByBsn(?string $bsn): Identity|Model|null
    {
        if (empty($bsn)) {
            return null;
        }

        return IdentityQuery::whereBsn(Identity::query(), $bsn)->first();
    }

    /**
     * @param string $email
     * @param bool $verified
     * @param bool $primary
     * @param bool $initial
     * @return IdentityEmail|BaseModel
     */
    public function addEmail(
        string $email,
        bool $verified = false,
        bool $primary = false,
        bool $initial = false
    ): IdentityEmail|Model {
        return $this->emails()->create(array_merge(compact(
            'email',
            'verified',
            'primary',
            'initial'
        ), [
            'verification_token' => token_generator()->generate(200),
        ]));
    }

    /**
     * @return string|null
     * @noinspection PhpUnused
     */
    public function getEmailAttribute(): ?string
    {
        return $this->primary_email?->email;
    }

    /**
     * @return string|null
     * @noinspection PhpUnused
     */
    public function getBsnAttribute(): ?string
    {
        return $this->record_bsn?->value;
    }

    /**
     * @return string|null
     * @noinspection PhpUnused
     */
    public function getTypeLocaleAttribute(): ?string
    {
        return match ($this->type) {
            self::TYPE_EMPLOYEE => 'Employee user',
            self::TYPE_PROFILE => 'Manually created',
            default => 'Regular requester',
        };
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function routeNotificationForMail(): string
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     * @param array $records
     * @param string|null $type
     * @param int|null $organizationId
     * @param int|null $employeeId
     * @return Identity
     */
    public static function build(
        ?string $email = null,
        array $records = [],
        ?string $type = null,
        ?int $employeeId = null,
        ?int $organizationId = null,
    ): static {
        $identity = static::create([
            'address' => resolve('token_generator')->address(),
            'passphrase' => resolve('token_generator')->generate(32),
            'type' => $type,
            'creator_employee_id' => $employeeId,
            'creator_organization_id' => $organizationId,
        ]);

        if (!empty($email)) {
            $identity->addEmail($email, false, true, true);
        }

        $identity->createRecordCategory('Relaties');
        $identity->addRecords($records);

        return $identity;
    }

    /**
     * @param string $email
     * @param string|null $type
     * @param int|null $organizationId
     * @param int|null $employeeId
     * @return Identity
     */
    public static function findOrBuild(
        string $email,
        ?string $type = null,
        ?int $employeeId = null,
        ?int $organizationId = null,
    ): static {
        return self::findByEmail($email) ?: static::build(
            email: $email,
            type: $type,
            employeeId: $employeeId,
            organizationId: $organizationId,
        );
    }

    /**
     * @param array|string[] $records
     * @return Collection|Record[]
     */
    public function addRecords(array $records = []): Collection|array
    {
        $recordTypes = RecordType::pluck('id', 'key')->toArray();

        return $this->records()->createMany(array_map(fn ($key) => [
            'record_type_id' => $recordTypes[$key],
            'value' => $records[$key],
        ], array_keys($records)));
    }

    /**
     * Add new record category to identity.
     * @param string $name
     * @param int $order
     * @return RecordCategory|BaseModel
     */
    public function createRecordCategory(string $name, int $order = 0): RecordCategory|Model
    {
        return $this->record_categories()->create(compact('name', 'order'));
    }

    /**
     * Create new proxy for given identity.
     * @return IdentityProxy
     */
    public function makeIdentityPoxy(): IdentityProxy
    {
        return $this->makeProxy('confirmation_code', $this);
    }

    /**
     * Make code authorization proxy identity.
     * @return IdentityProxy
     */
    public static function makeAuthorizationCodeProxy(): IdentityProxy
    {
        return static::makeProxy('pin_code');
    }

    /**
     * Make token authorization proxy identity.
     * @return IdentityProxy
     */
    public static function makeAuthorizationTokenProxy(): IdentityProxy
    {
        return static::makeProxy('qr_code');
    }

    /**
     * Make token authorization proxy identity.
     * @return IdentityProxy
     */
    public static function makeAuthorizationShortTokenProxy(): IdentityProxy
    {
        return static::makeProxy('short_token');
    }

    /**
     * Make email token authorization proxy identity.
     * @return IdentityProxy
     */
    public function makeAuthorizationEmailProxy(): IdentityProxy
    {
        return static::makeProxy('email_code', $this);
    }

    /**
     * @param string $type
     * @param Identity|null $identity
     * @param string $state
     * @return IdentityProxy
     */
    public static function makeProxy(
        string $type,
        ?Identity $identity = null,
        string $state = 'pending',
    ): IdentityProxy {
        try {
            $exchangeToken = static::uniqExchangeToken($type);
        } catch (Throwable $e) {
            logger()->error($e->getMessage());
            abort(400);
        }

        return static::createProxy($exchangeToken, $type, static::EXPIRATION_TIMES[$type], $identity, $state);
    }

    /**
     * Authorize proxy identity by code.
     * @param string $code
     * @param string|null $ip
     * @param IdentityProxy|null $inherit2FA
     * @return bool
     */
    public function activateAuthorizationCodeProxy(
        string $code,
        ?string $ip = null,
        ?IdentityProxy $inherit2FA = null,
    ): bool {
        return (bool) static::exchangeToken('pin_code', $code, $this, $ip, $inherit2FA);
    }

    /**
     * Authorize proxy identity by token.
     * @param string $token
     * @param string|null $ip
     * @param IdentityProxy|null $inherit2FA
     * @return bool
     */
    public function activateAuthorizationTokenProxy(
        string $token,
        ?string $ip = null,
        ?IdentityProxy $inherit2FA = null,
    ): bool {
        return (bool) static::exchangeToken('qr_code', $token, $this, $ip, $inherit2FA);
    }

    /**
     * Authorize proxy identity by token.
     * @param string $token
     * @param string|null $ip
     * @return bool
     */
    public function activateAuthorizationShortTokenProxy(string $token, ?string $ip = null): bool
    {
        return (bool) static::exchangeToken('short_token', $token, $this, $ip);
    }

    /**
     * Authorize proxy identity by token.
     * @param string $token
     * @return IdentityProxy
     */
    public static function exchangeAuthorizationShortTokenProxy(string $token): IdentityProxy
    {
        $proxy = static::proxyByExchangeToken($token, 'short_token');

        if (empty($proxy?->access_token)) {
            abort(404, trans('identity-proxy.code.not-found'));
        }

        if ($proxy->exchange_time_expired) {
            abort(403, trans('identity-proxy.code.expired'));
        }

        return $proxy;
    }

    /**
     * Authorize proxy identity by email token.
     * @param string $token
     * @param string|null $ip
     * @return string
     */
    public static function activateAuthorizationEmailProxy(string $token, ?string $ip = null): string
    {
        return static::exchangeToken('email_code', $token, null, $ip)->access_token;
    }

    /**
     * Authorize proxy identity by email token.
     * @param string $token
     * @param string|null $ip
     * @return string
     */
    public static function exchangeEmailConfirmationToken(string $token, ?string $ip = null): string
    {
        return static::exchangeToken('confirmation_code', $token, null, $ip)->access_token;
    }

    /**
     * @param string $email
     * @return bool
     */
    public static function isEmailAvailable(string $email): bool
    {
        return IdentityEmail::whereEmail($email)->doesntExist();
    }

    /**
     * @param RecordType $type
     * @param string $value
     * @param int|null $recordCategoryId
     * @param int|null $order
     * @param FundRequest|null $fundRequest
     * @param Prevalidation|null $prevalidation
     * @return array|null
     */
    public function makeRecord(
        RecordType $type,
        string $value,
        ?int $recordCategoryId = null,
        ?int $order = null,
        ?FundRequest $fundRequest = null,
        ?Prevalidation $prevalidation = null,
    ): ?Record {
        $hasRecordOfSameType = $this->records()->where([
            'record_type_id' => $type->id,
        ])->exists();

        if ($type->key === 'primary_email' && $hasRecordOfSameType) {
            abort(403, 'record.exceptions.primary_email_already_exists');
        }

        if ($type->key === 'bsn') {
            abort(403, 'record.exceptions.bsn_record_cant_be_created');
        }

        return Record::create([
            'identity_address' => $this->address,
            'order' => $order ?: 0,
            'value' => $value,
            'record_type_id' => $type->id,
            'record_category_id' => $recordCategoryId,
            'fund_request_id' => $fundRequest?->id,
            'prevalidation_id' => $prevalidation?->id,
        ]);
    }

    /**
     * @param string $bsnValue
     * @return Record|BaseModel
     */
    public function setBsnRecord(string $bsnValue): Record|Model
    {
        $recordType = RecordType::findByKey('bsn');

        if ($this->bsn && $this->bsn !== $bsnValue) {
            abort(403, 'record.exceptions.bsn_record_cant_be_changed');
        }

        $record = $this->records()->create([
            'order' => 0,
            'value' => $bsnValue,
            'record_type_id' => $recordType->id,
            'record_category_id' => null,
        ]);

        $this->load('record_bsn');

        return $record;
    }

    /**
     * @return string
     */
    public function getAuthIdentifier(): string
    {
        return $this->address;
    }

    /**
     * @return string
     */
    public function getAuthIdentifierName(): string
    {
        return 'address';
    }

    /**
     * @return null
     */
    public function getAuthPassword(): null
    {
        return null;
    }

    /**
     * @return null
     */
    public function getRememberToken(): null
    {
        return null;
    }

    /**
     * @return null
     */
    public function getRememberTokenName(): null
    {
        return null;
    }

    /**
     * @param $value
     * @return null
     */
    public function setRememberToken($value): null
    {
        return null;
    }

    /**
     * @return bool
     */
    public function is2FARequired(): bool
    {
        // 2FA is configured by the identity
        if ($this->is2FAConfigured()) {
            return true;
        }

        // Identity is an employee of an organization where 2fa is required
        if ($this->isEmployeeWhere2FAIsRequired()) {
            return true;
        }

        // Identity has a voucher from a fund where 2fa is required
        if ($this->hasVouchersFromFundsWhere2FAIsRequired()) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isEmployeeWhere2FAIsRequired(): bool
    {
        return $this->employees()->whereRelation(
            'organization',
            'auth_2fa_policy',
            Organization::AUTH_2FA_POLICY_REQUIRED,
        )->exists();
    }

    /**
     * @return bool
     */
    public function hasVouchersFromFundsWhere2FAIsRequired(): bool
    {
        return $this->vouchers()->whereHas('fund', function (Builder $builder) {
            $builder->whereRelation(
                'fund_config',
                'auth_2fa_policy',
                FundConfig::AUTH_2FA_POLICY_REQUIRED
            )->orWhere(function (Builder $builder) {
                $builder->whereRelation(
                    'fund_config',
                    'auth_2fa_policy',
                    FundConfig::AUTH_2FA_POLICY_GLOBAL,
                );

                $builder->whereRelation(
                    'fund_config.fund.organization',
                    'auth_2fa_funds_policy',
                    Organization::AUTH_2FA_FUNDS_POLICY_REQUIRED,
                );
            });
        })->exists();
    }

    /**
     * @param bool $fresh
     * @return bool
     */
    public function is2FAConfigured(bool $fresh = false): bool
    {
        return $fresh ?
            $this->identity_2fa_active()->exists() :
            $this->identity_2fa_active->isNotEmpty();
    }

    /**
     * @param string $company
     * @param string $holder
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     * @return Auth2FASecret
     */
    public function make2FASecret(string $company, string $holder): Auth2FASecret
    {
        return resolve(Auth2FAService::class)->make2FASecret($company, $holder);
    }

    /**
     * @param string $feature
     * @return SupportCollection
     */
    public function getRestricting2FAOrganizations(string $feature): SupportCollection
    {
        return $this->employees->reduce(function (SupportCollection $list, Employee $employee) use ($feature) {
            if ($feature === 'bi_connections' && $employee->organization->auth_2fa_restrict_bi_connections) {
                return $list->push($employee->organization);
            }

            return $list;
        }, collect())->values();
    }

    /**
     * @param string $feature
     * @return SupportCollection
     */
    public function getRestricting2FAFunds(string $feature): SupportCollection
    {
        return $this->funds->filter(function (Fund $fund) use ($feature) {
            if ($fund->fund_config->auth_2fa_policy != FundConfig::AUTH_2FA_POLICY_GLOBAL) {
                return $fund->fund_config?->{match($feature) {
                    'emails' => 'auth_2fa_restrict_emails',
                    'sessions' => 'auth_2fa_restrict_auth_sessions',
                    'reimbursements' => 'auth_2fa_restrict_reimbursements',
                }} ?? false;
            }

            if ($fund->organization->auth_2fa_funds_policy == Organization::AUTH_2FA_FUNDS_POLICY_RESTRICT) {
                return $fund->organization->{match($feature) {
                    'emails' => 'auth_2fa_funds_restrict_emails',
                    'sessions' => 'auth_2fa_funds_restrict_auth_sessions',
                    'reimbursements' => 'auth_2fa_funds_restrict_reimbursements',
                }} ?? false;
            }

            return false;
        })->values();
    }

    /**
     * @param string $feature
     * @return bool
     */
    public function isFeature2FARestricted(string $feature): bool
    {
        if ($feature === 'bi_connections') {
            return $this->getRestricting2FAOrganizations($feature)->isNotEmpty();
        }

        return $this->getRestricting2FAFunds($feature)->isNotEmpty();
    }

    /**
     * @return string
     */
    public function getCurrent2FAHolderName(): string
    {
        return $this->email ?: 'Gebruiker [' . Str::upper(substr($this->address, 2, 10)) . ']';
    }

    /**
     * @return string
     */
    protected static function makeAccessToken(): string
    {
        return static::makeToken(200);
    }

    /**
     * @param int $size
     * @return string
     */
    protected static function makeToken(int $size): string
    {
        return app('token_generator')->generate($size);
    }

    /**
     * @return BelongsToMany
     * @noinspection PhpUnused
     */
    private function auth_2fa_providers(): BelongsToMany
    {
        return $this->belongsToMany(
            Auth2FAProvider::class,
            'identity_2fa',
            'identity_address',
            'auth_2fa_provider_id',
            'address',
        );
    }

    /**
     * @param $type
     * @throws Throwable
     * @return string
     */
    private static function uniqExchangeToken($type): string
    {
        do {
            $token = match ($type) {
                'qr_code' => static::makeToken(64),
                'pin_code' => (string) random_int(111111, 999999),
                'email_code' => static::makeToken(128),
                'short_token', 'confirmation_code' => static::makeToken(200),
                default => throw new Exception(trans('identity-proxy.unknown_token_type')),
            };
        } while (IdentityProxy::whereAccessToken($token)->exists());

        return $token;
    }

    /**
     * Create new proxy.
     *
     * @param string $exchange_token
     * @param string $type
     * @param int $expires_in
     * @param Identity|null $identity
     * @param string $state
     * @return IdentityProxy
     */
    private static function createProxy(
        string $exchange_token,
        string $type,
        int $expires_in,
        Identity $identity = null,
        string $state = 'pending'
    ): IdentityProxy {
        $access_token = static::makeAccessToken();

        return IdentityProxy::create(array_merge([
            'identity_address' => $identity?->address,
        ], compact('exchange_token', 'type', 'expires_in', 'state', 'access_token')));
    }

    /**
     * Activate proxy by exchange_token.
     *
     * @param string $type
     * @param string $exchangeToken
     * @param Identity|null $identity
     * @param string|null $ip
     * @param IdentityProxy|null $inherit2FA
     * @return IdentityProxy
     */
    private static function exchangeToken(
        string $type,
        string $exchangeToken,
        Identity $identity = null,
        ?string $ip = null,
        ?IdentityProxy $inherit2FA = null,
    ): IdentityProxy {
        $proxy = IdentityProxy::findByExchangeToken($exchangeToken, $type);

        if (empty($proxy)) {
            abort(404, trans('identity-proxy.code.not-found'));
        }

        if (!$proxy->isPending()) {
            abort(403, trans('identity-proxy.code.not-pending'));
        }

        if ($proxy->exchange_time_expired) {
            abort(403, trans('identity-proxy.code.expired'));
        }

        // Update identity_address only if provided
        $proxy->update(array_merge([
            'state' => IdentityProxy::STATE_ACTIVE,
            'activated_at' => now(),
        ], $identity ? [
            'identity_address' => $identity->address,
        ] : []));

        $initialEmail = $proxy->identity->initial_email;
        $isEmailToken = in_array($type, ['email_code', 'confirmation_code']);

        if ($inherit2FA) {
            $proxy->inherit2FAStateFrom($inherit2FA);
        } elseif ($ip) {
            $proxy->inherit2FAState($ip, Config::get('forus.auth_2fa.remember_hours'));
        }

        if ($isEmailToken && $initialEmail && !$initialEmail->verified) {
            $initialEmail->setVerified();
        }

        return $proxy;
    }

    /**
     * @param $exchange_token
     * @param $type
     * @return IdentityProxy|null
     */
    private static function proxyByExchangeToken($exchange_token, $type): ?IdentityProxy
    {
        return IdentityProxy::where([
            'exchange_token' => $exchange_token,
            'type' => $type,
        ])->first();
    }
}
