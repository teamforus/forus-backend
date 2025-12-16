<?php

namespace App\Models;

use App\Events\Employees\EmployeeCreated;
use App\Models\Traits\HasTags;
use App\Scopes\Builders\EmployeeQuery;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\IdentityQuery;
use App\Scopes\Builders\OrganizationQuery;
use App\Scopes\Builders\ProductQuery;
use App\Services\BankService\Models\Bank;
use App\Services\BIConnectionService\Models\BIConnection;
use App\Services\EventLogService\Traits\HasDigests;
use App\Services\EventLogService\Traits\HasLogs;
use App\Services\Forus\Session\Models\Session;
use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Traits\HasMedia;
use App\Services\MollieService\Models\MollieConnection;
use App\Services\TranslationService\Traits\HasOnDemandTranslations;
use App\Statistics\Funds\FinancialStatisticQueries;
use App\Traits\HasMarkdownFields;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection as SupportCollection;

/**
 * App\Models\Organization.
 *
 * @property int $id
 * @property string|null $identity_address
 * @property string $name
 * @property string|null $description
 * @property string|null $description_text
 * @property string $iban
 * @property string $email
 * @property bool $email_public
 * @property string $phone
 * @property bool $phone_public
 * @property string $kvk
 * @property string $btw
 * @property string|null $website
 * @property bool $website_public
 * @property int $business_type_id
 * @property bool $is_sponsor
 * @property bool $is_provider
 * @property bool $is_validator
 * @property bool $reservations_enabled
 * @property bool $translations_enabled
 * @property int $translations_daily_limit
 * @property int $translations_weekly_limit
 * @property int $translations_monthly_limit
 * @property bool $reservations_auto_accept
 * @property string $reservation_phone
 * @property string $reservation_address
 * @property string $reservation_birth_date
 * @property string $reservation_user_note
 * @property bool $reservation_note
 * @property string|null $reservation_note_text
 * @property bool $manage_provider_products
 * @property bool $backoffice_available
 * @property bool $allow_batch_reservations
 * @property bool $allow_custom_fund_notifications
 * @property bool $allow_manual_bulk_processing
 * @property bool $allow_2fa_restrictions
 * @property bool $allow_fund_request_record_edit
 * @property bool $allow_bi_connection
 * @property bool $allow_physical_cards
 * @property bool $allow_provider_extra_payments
 * @property bool $allow_pre_checks
 * @property bool $allow_payouts
 * @property bool $allow_profiles
 * @property bool $allow_profiles_create
 * @property bool $allow_profiles_relations
 * @property bool $allow_profiles_households
 * @property bool $allow_translations
 * @property bool $allow_product_updates
 * @property bool $reservation_allow_extra_payments
 * @property int $pre_approve_external_funds
 * @property int $provider_throttling_value
 * @property string $fund_request_resolve_policy
 * @property bool $bsn_enabled
 * @property string|null $bank_cron_time
 * @property string|null $auth_2fa_policy
 * @property bool|null $auth_2fa_remember_ip
 * @property string $auth_2fa_funds_policy
 * @property bool $auth_2fa_funds_remember_ip
 * @property bool $auth_2fa_funds_restrict_emails
 * @property bool $auth_2fa_funds_restrict_auth_sessions
 * @property bool $auth_2fa_funds_restrict_reimbursements
 * @property bool $auth_2fa_restrict_bi_connections
 * @property bool $show_provider_transactions
 * @property bool $bank_transaction_id
 * @property bool $bank_transaction_date
 * @property bool $bank_transaction_time
 * @property bool $bank_reservation_number
 * @property bool $bank_reservation_first_name
 * @property bool $bank_reservation_last_name
 * @property bool $bank_reservation_invoice_number
 * @property bool $bank_branch_number
 * @property bool $bank_branch_id
 * @property bool $bank_branch_name
 * @property bool $bank_fund_name
 * @property bool $bank_note
 * @property string $bank_separator
 * @property string|null $iconnect_target_binding
 * @property string|null $iconnect_api_oin
 * @property string|null $iconnect_base_url
 * @property string $iconnect_env
 * @property string $iconnect_key
 * @property string $iconnect_key_pass
 * @property string $iconnect_cert
 * @property string $iconnect_cert_pass
 * @property string $iconnect_cert_trust
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\BankConnection|null $bank_connection_active
 * @property-read Collection|\App\Models\BankConnection[] $bank_connections
 * @property-read int|null $bank_connections_count
 * @property-read BIConnection|null $bi_connection
 * @property-read \App\Models\BusinessType $business_type
 * @property-read Collection|\App\Models\OrganizationContact[] $contacts
 * @property-read int|null $contacts_count
 * @property-read Collection|\App\Services\EventLogService\Models\Digest[] $digests
 * @property-read int|null $digests_count
 * @property-read Collection|\App\Models\Employee[] $employees
 * @property-read int|null $employees_count
 * @property-read Collection|\App\Models\Employee[] $employees_with_trashed
 * @property-read int|null $employees_with_trashed_count
 * @property-read Collection|\App\Models\FundForm[] $fund_forms
 * @property-read int|null $fund_forms_count
 * @property-read Collection|\App\Models\FundProviderInvitation[] $fund_provider_invitations
 * @property-read int|null $fund_provider_invitations_count
 * @property-read Collection|\App\Models\FundProvider[] $fund_providers
 * @property-read int|null $fund_providers_count
 * @property-read Collection|\App\Models\FundProvider[] $fund_providers_allowed_extra_payments
 * @property-read int|null $fund_providers_allowed_extra_payments_count
 * @property-read Collection|\App\Models\FundProvider[] $fund_providers_allowed_extra_payments_full
 * @property-read int|null $fund_providers_allowed_extra_payments_full_count
 * @property-read Collection|\App\Models\FundRequest[] $fund_requests
 * @property-read int|null $fund_requests_count
 * @property-read Collection|\App\Models\Fund[] $funds
 * @property-read int|null $funds_count
 * @property-read Collection|\App\Models\Fund[] $funds_active
 * @property-read int|null $funds_active_count
 * @property-read string $description_html
 * @property-read \App\Models\Identity|null $identity
 * @property-read Collection|\App\Models\Implementation[] $implementations
 * @property-read int|null $implementations_count
 * @property-read Session|null $last_employee_session
 * @property-read Media|null $logo
 * @property-read Collection|\App\Services\EventLogService\Models\EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read Collection|Media[] $medias
 * @property-read int|null $medias_count
 * @property-read MollieConnection|null $mollie_connection
 * @property-read Collection|MollieConnection[] $mollie_connections
 * @property-read int|null $mollie_connections_count
 * @property-read Collection|\App\Models\Office[] $offices
 * @property-read int|null $offices_count
 * @property-read Collection|\App\Models\PhysicalCardType[] $physical_card_types
 * @property-read int|null $physical_card_types_count
 * @property-read Collection|\App\Models\Prevalidation[] $prevalidations
 * @property-read int|null $prevalidations_count
 * @property-read Collection|\App\Models\Product[] $products
 * @property-read int|null $products_count
 * @property-read Collection|\App\Models\Product[] $products_as_sponsor
 * @property-read int|null $products_as_sponsor_count
 * @property-read Collection|\App\Models\Product[] $products_provider
 * @property-read int|null $products_provider_count
 * @property-read Collection|\App\Models\Product[] $products_sponsor
 * @property-read int|null $products_sponsor_count
 * @property-read Collection|\App\Models\Profile[] $profiles
 * @property-read int|null $profiles_count
 * @property-read Collection|\App\Models\ReimbursementCategory[] $reimbursement_categories
 * @property-read int|null $reimbursement_categories_count
 * @property-read Collection|\App\Models\ReservationField[] $reservation_fields
 * @property-read int|null $reservation_fields_count
 * @property-read Collection|\App\Models\Fund[] $supplied_funds
 * @property-read int|null $supplied_funds_count
 * @property-read Collection|\App\Models\Tag[] $tags
 * @property-read int|null $tags_count
 * @property-read Collection|\App\Services\TranslationService\Models\TranslationValue[] $translation_values
 * @property-read int|null $translation_values_count
 * @property-read Collection|\App\Models\VoucherTransactionBulk[] $voucher_transaction_bulks
 * @property-read int|null $voucher_transaction_bulks_count
 * @property-read Collection|\App\Models\VoucherTransaction[] $voucher_transactions
 * @property-read int|null $voucher_transactions_count
 * @property-read Collection|\App\Models\Voucher[] $vouchers
 * @property-read int|null $vouchers_count
 * @method static EloquentBuilder<static>|Organization newModelQuery()
 * @method static EloquentBuilder<static>|Organization newQuery()
 * @method static EloquentBuilder<static>|Organization query()
 * @method static EloquentBuilder<static>|Organization whereAllow2faRestrictions($value)
 * @method static EloquentBuilder<static>|Organization whereAllowBatchReservations($value)
 * @method static EloquentBuilder<static>|Organization whereAllowBiConnection($value)
 * @method static EloquentBuilder<static>|Organization whereAllowCustomFundNotifications($value)
 * @method static EloquentBuilder<static>|Organization whereAllowFundRequestRecordEdit($value)
 * @method static EloquentBuilder<static>|Organization whereAllowManualBulkProcessing($value)
 * @method static EloquentBuilder<static>|Organization whereAllowPayouts($value)
 * @method static EloquentBuilder<static>|Organization whereAllowPhysicalCards($value)
 * @method static EloquentBuilder<static>|Organization whereAllowPreChecks($value)
 * @method static EloquentBuilder<static>|Organization whereAllowProductUpdates($value)
 * @method static EloquentBuilder<static>|Organization whereAllowProfiles($value)
 * @method static EloquentBuilder<static>|Organization whereAllowProfilesCreate($value)
 * @method static EloquentBuilder<static>|Organization whereAllowProfilesHouseholds($value)
 * @method static EloquentBuilder<static>|Organization whereAllowProfilesRelations($value)
 * @method static EloquentBuilder<static>|Organization whereAllowProviderExtraPayments($value)
 * @method static EloquentBuilder<static>|Organization whereAllowTranslations($value)
 * @method static EloquentBuilder<static>|Organization whereAuth2faFundsPolicy($value)
 * @method static EloquentBuilder<static>|Organization whereAuth2faFundsRememberIp($value)
 * @method static EloquentBuilder<static>|Organization whereAuth2faFundsRestrictAuthSessions($value)
 * @method static EloquentBuilder<static>|Organization whereAuth2faFundsRestrictEmails($value)
 * @method static EloquentBuilder<static>|Organization whereAuth2faFundsRestrictReimbursements($value)
 * @method static EloquentBuilder<static>|Organization whereAuth2faPolicy($value)
 * @method static EloquentBuilder<static>|Organization whereAuth2faRememberIp($value)
 * @method static EloquentBuilder<static>|Organization whereAuth2faRestrictBiConnections($value)
 * @method static EloquentBuilder<static>|Organization whereBackofficeAvailable($value)
 * @method static EloquentBuilder<static>|Organization whereBankBranchId($value)
 * @method static EloquentBuilder<static>|Organization whereBankBranchName($value)
 * @method static EloquentBuilder<static>|Organization whereBankBranchNumber($value)
 * @method static EloquentBuilder<static>|Organization whereBankCronTime($value)
 * @method static EloquentBuilder<static>|Organization whereBankFundName($value)
 * @method static EloquentBuilder<static>|Organization whereBankNote($value)
 * @method static EloquentBuilder<static>|Organization whereBankReservationFirstName($value)
 * @method static EloquentBuilder<static>|Organization whereBankReservationInvoiceNumber($value)
 * @method static EloquentBuilder<static>|Organization whereBankReservationLastName($value)
 * @method static EloquentBuilder<static>|Organization whereBankReservationNumber($value)
 * @method static EloquentBuilder<static>|Organization whereBankSeparator($value)
 * @method static EloquentBuilder<static>|Organization whereBankTransactionDate($value)
 * @method static EloquentBuilder<static>|Organization whereBankTransactionId($value)
 * @method static EloquentBuilder<static>|Organization whereBankTransactionTime($value)
 * @method static EloquentBuilder<static>|Organization whereBsnEnabled($value)
 * @method static EloquentBuilder<static>|Organization whereBtw($value)
 * @method static EloquentBuilder<static>|Organization whereBusinessTypeId($value)
 * @method static EloquentBuilder<static>|Organization whereCreatedAt($value)
 * @method static EloquentBuilder<static>|Organization whereDescription($value)
 * @method static EloquentBuilder<static>|Organization whereDescriptionText($value)
 * @method static EloquentBuilder<static>|Organization whereEmail($value)
 * @method static EloquentBuilder<static>|Organization whereEmailPublic($value)
 * @method static EloquentBuilder<static>|Organization whereFundRequestResolvePolicy($value)
 * @method static EloquentBuilder<static>|Organization whereIban($value)
 * @method static EloquentBuilder<static>|Organization whereIconnectApiOin($value)
 * @method static EloquentBuilder<static>|Organization whereIconnectBaseUrl($value)
 * @method static EloquentBuilder<static>|Organization whereIconnectCert($value)
 * @method static EloquentBuilder<static>|Organization whereIconnectCertPass($value)
 * @method static EloquentBuilder<static>|Organization whereIconnectCertTrust($value)
 * @method static EloquentBuilder<static>|Organization whereIconnectEnv($value)
 * @method static EloquentBuilder<static>|Organization whereIconnectKey($value)
 * @method static EloquentBuilder<static>|Organization whereIconnectKeyPass($value)
 * @method static EloquentBuilder<static>|Organization whereIconnectTargetBinding($value)
 * @method static EloquentBuilder<static>|Organization whereId($value)
 * @method static EloquentBuilder<static>|Organization whereIdentityAddress($value)
 * @method static EloquentBuilder<static>|Organization whereIsProvider($value)
 * @method static EloquentBuilder<static>|Organization whereIsSponsor($value)
 * @method static EloquentBuilder<static>|Organization whereIsValidator($value)
 * @method static EloquentBuilder<static>|Organization whereKvk($value)
 * @method static EloquentBuilder<static>|Organization whereManageProviderProducts($value)
 * @method static EloquentBuilder<static>|Organization whereName($value)
 * @method static EloquentBuilder<static>|Organization wherePhone($value)
 * @method static EloquentBuilder<static>|Organization wherePhonePublic($value)
 * @method static EloquentBuilder<static>|Organization wherePreApproveExternalFunds($value)
 * @method static EloquentBuilder<static>|Organization whereProviderThrottlingValue($value)
 * @method static EloquentBuilder<static>|Organization whereReservationAddress($value)
 * @method static EloquentBuilder<static>|Organization whereReservationAllowExtraPayments($value)
 * @method static EloquentBuilder<static>|Organization whereReservationBirthDate($value)
 * @method static EloquentBuilder<static>|Organization whereReservationNote($value)
 * @method static EloquentBuilder<static>|Organization whereReservationNoteText($value)
 * @method static EloquentBuilder<static>|Organization whereReservationPhone($value)
 * @method static EloquentBuilder<static>|Organization whereReservationUserNote($value)
 * @method static EloquentBuilder<static>|Organization whereReservationsAutoAccept($value)
 * @method static EloquentBuilder<static>|Organization whereReservationsEnabled($value)
 * @method static EloquentBuilder<static>|Organization whereShowProviderTransactions($value)
 * @method static EloquentBuilder<static>|Organization whereTranslationsDailyLimit($value)
 * @method static EloquentBuilder<static>|Organization whereTranslationsEnabled($value)
 * @method static EloquentBuilder<static>|Organization whereTranslationsMonthlyLimit($value)
 * @method static EloquentBuilder<static>|Organization whereTranslationsWeeklyLimit($value)
 * @method static EloquentBuilder<static>|Organization whereUpdatedAt($value)
 * @method static EloquentBuilder<static>|Organization whereWebsite($value)
 * @method static EloquentBuilder<static>|Organization whereWebsitePublic($value)
 * @mixin \Eloquent
 */
class Organization extends BaseModel
{
    use HasLogs;
    use HasTags;
    use HasMedia;
    use HasDigests;
    use HasMarkdownFields;
    use HasOnDemandTranslations;

    public const string GENERIC_KVK = '00000000';

    public const string FUND_REQUEST_POLICY_MANUAL = 'apply_manually';
    public const string FUND_REQUEST_POLICY_AUTO_REQUESTED = 'apply_auto_requested';
    public const string FUND_REQUEST_POLICY_AUTO_AVAILABLE = 'apply_auto_available';

    public const string AUTH_2FA_POLICY_OPTIONAL = 'optional';
    public const string AUTH_2FA_POLICY_REQUIRED = 'required';

    public const string AUTH_2FA_FUNDS_POLICY_OPTIONAL = 'optional';
    public const string AUTH_2FA_FUNDS_POLICY_REQUIRED = 'required';
    public const string AUTH_2FA_FUNDS_POLICY_RESTRICT = 'restrict_features';

    public const array AUTH_2FA_POLICIES = [
        self::AUTH_2FA_POLICY_OPTIONAL,
        self::AUTH_2FA_POLICY_REQUIRED,
    ];

    public const string EVENT_BI_CONNECTION_UPDATED = 'bi_connection_updated';

    public const array AUTH_2FA_FUNDS_POLICIES = [
        self::AUTH_2FA_FUNDS_POLICY_OPTIONAL,
        self::AUTH_2FA_FUNDS_POLICY_REQUIRED,
        self::AUTH_2FA_FUNDS_POLICY_RESTRICT,
    ];

    public const array BANK_SEPARATORS = ['-', '/', '+', ':', '--', '//', '++', '::'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'identity_address', 'name', 'iban', 'email', 'email_public',
        'phone', 'phone_public', 'kvk', 'btw', 'website', 'website_public',
        'business_type_id', 'is_sponsor', 'is_provider', 'is_validator',
        'manage_provider_products', 'description', 'description_text',
        'backoffice_available', 'reservations_enabled',
        'reservations_auto_accept', 'bsn_enabled', 'allow_custom_fund_notifications',
        'reservation_phone', 'reservation_address', 'reservation_birth_date', 'reservation_user_note',
        'allow_bi_connection', 'auth_2fa_policy', 'auth_2fa_remember_ip', 'allow_2fa_restrictions',
        'auth_2fa_funds_policy', 'auth_2fa_funds_remember_ip', 'auth_2fa_funds_restrict_emails',
        'auth_2fa_funds_restrict_auth_sessions', 'auth_2fa_funds_restrict_reimbursements',
        'reservation_allow_extra_payments', 'allow_provider_extra_payments',
        'auth_2fa_restrict_bi_connections',
        'bank_transaction_id', 'bank_transaction_date', 'bank_transaction_time',
        'bank_branch_number', 'bank_branch_id', 'bank_branch_name', 'bank_fund_name',
        'bank_note', 'bank_reservation_number', 'bank_separator', 'translations_enabled',
        'translations_daily_limit', 'translations_weekly_limit', 'translations_monthly_limit',
        'bank_reservation_first_name', 'bank_reservation_last_name', 'reservation_note',
        'reservation_note_text', 'bank_reservation_invoice_number',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'btw' => 'string',
        'email_public' => 'boolean',
        'phone_public' => 'boolean',
        'website_public' => 'boolean',
        'is_sponsor' => 'boolean',
        'is_provider' => 'boolean',
        'is_validator' => 'boolean',
        'backoffice_available' => 'boolean',
        'manage_provider_products' => 'boolean',
        'reservations_enabled' => 'boolean',
        'translations_enabled' => 'boolean',
        'reservations_auto_accept' => 'boolean',
        'allow_batch_reservations' => 'boolean',
        'allow_custom_fund_notifications' => 'boolean',
        'allow_manual_bulk_processing' => 'boolean',
        'allow_2fa_restrictions' => 'boolean',
        'allow_fund_request_record_edit' => 'boolean',
        'allow_bi_connection' => 'boolean',
        'allow_physical_cards' => 'boolean',
        'allow_product_updates' => 'boolean',
        'allow_profiles_create' => 'boolean',
        'allow_profiles_relations' => 'boolean',
        'allow_profiles_households' => 'boolean',
        'bsn_enabled' => 'boolean',
        'auth_2fa_remember_ip' => 'boolean',
        'auth_2fa_funds_remember_ip' => 'boolean',
        'auth_2fa_funds_restrict_emails' => 'boolean',
        'auth_2fa_funds_restrict_auth_sessions' => 'boolean',
        'auth_2fa_funds_restrict_reimbursements' => 'boolean',
        'auth_2fa_restrict_bi_connections' => 'boolean',
        'allow_provider_extra_payments' => 'boolean',
        'allow_pre_checks' => 'boolean',
        'allow_payouts' => 'boolean',
        'allow_profiles' => 'boolean',
        'allow_translations' => 'boolean',
        'reservation_allow_extra_payments' => 'boolean',
        'show_provider_transactions' => 'boolean',
        'bank_transaction_id' => 'boolean',
        'bank_transaction_date' => 'boolean',
        'bank_transaction_time' => 'boolean',
        'bank_reservation_number' => 'boolean',
        'bank_reservation_first_name' => 'boolean',
        'bank_reservation_last_name' => 'boolean',
        'bank_branch_number' => 'boolean',
        'bank_branch_id' => 'boolean',
        'bank_branch_name' => 'boolean',
        'bank_fund_name' => 'boolean',
        'bank_note' => 'boolean',
        'reservation_note' => 'boolean',
        'bank_reservation_invoice_number' => 'boolean',
    ];

    /**
     * @var string[]
     */
    protected $hidden = [
        'iconnect_api_oin', 'iconnect_target_binding', 'iconnect_base_url', 'iconnect_env',
        'iconnect_key', 'iconnect_key_pass', 'iconnect_cert', 'iconnect_cert_pass',
        'iconnect_cert_trust',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'identity_address', 'address');
    }

    /**
     * @param bool $external
     * @return string
     */
    public function initialFundState(bool $external): string
    {
        if ($external && $this->pre_approve_external_funds) {
            return Fund::STATE_PAUSED;
        }

        return Fund::STATE_WAITING;
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function reimbursement_categories(): HasMany
    {
        return $this->hasMany(ReimbursementCategory::class);
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function bank_connections(): HasMany
    {
        return $this->hasMany(BankConnection::class);
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function implementations(): HasMany
    {
        return $this->hasMany(Implementation::class);
    }

    /**
     * @return HasOne
     * @noinspection PhpUnused
     */
    public function bank_connection_active(): HasOne
    {
        return $this->hasOne(BankConnection::class)->where([
            'state' => BankConnection::STATE_ACTIVE,
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function funds(): HasMany
    {
        return $this->hasMany(Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function funds_active(): HasMany
    {
        return $this->hasMany(Fund::class)->where(function (EloquentBuilder $builder) {
            FundQuery::whereActiveFilter($builder);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function prevalidations(): HasMany
    {
        return $this->hasMany(Prevalidation::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function products_as_sponsor(): HasMany
    {
        return $this->hasMany(Product::class, 'sponsor_organization_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function products_provider(): HasMany
    {
        return $this->hasMany(Product::class)->whereNull('sponsor_organization_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function products_sponsor(): HasMany
    {
        return $this->hasMany(Product::class)->whereNotNull('sponsor_organization_id');
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
    public function offices(): HasMany
    {
        return $this->hasMany(Office::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function business_type(): BelongsTo
    {
        return $this->belongsTo(BusinessType::class);
    }

    /**
     * @return Organization|EloquentBuilder|HasManyThrough
     * @noinspection PhpUnused
     */
    public function fund_forms(): EloquentBuilder|HasManyThrough|Organization
    {
        return $this->hasManyThrough(FundForm::class, Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function voucher_transactions(): HasMany
    {
        return $this->hasMany(VoucherTransaction::class);
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function voucher_transaction_bulks(): HasMany
    {
        return $this->hasMany(VoucherTransactionBulk::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     * @noinspection PhpUnused
     */
    public function fund_requests(): HasManyThrough
    {
        return $this->hasManyThrough(FundRequest::class, Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     * @noinspection PhpUnused
     */
    public function supplied_funds(): BelongsToMany
    {
        return $this->belongsToMany(Fund::class, 'fund_providers');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function fund_provider_invitations(): HasMany
    {
        return $this->hasMany(FundProviderInvitation::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function fund_providers(): HasMany
    {
        return $this->hasMany(FundProvider::class);
    }

    /**
     * Get organization logo.
     * @return MorphOne
     * @noinspection PhpUnused
     */
    public function logo(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')->where([
            'type' => 'organization_logo',
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function vouchers(): HasManyThrough
    {
        return $this->hasManyThrough(Voucher::class, Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function employees_with_trashed(): HasMany
    {
        /** @var Employee|HasMany $relation */
        $relation = $this->hasMany(Employee::class);
        $relation->withTrashed();

        return $relation;
    }

    /**
     * @return HasOneThrough
     * @noinspection PhpUnused
     */
    public function last_employee_session(): HasOneThrough
    {
        return $this->hasOneThrough(
            Session::class,
            Employee::class,
            'organization_id',
            'identity_address',
            'id',
            'identity_address'
        )->latest('last_activity_at');
    }

    /**
     * @return HasMany
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(OrganizationContact::class);
    }

    /**
     * @return HasMany
     */
    public function reservation_fields(): HasMany
    {
        return $this->hasMany(ReservationField::class)
            ->whereNull('product_id')
            ->orderBy('order');
    }

    /**
     * @return HasOne
     * @noinspection PhpUnused
     */
    public function bi_connection(): HasOne
    {
        return $this->hasOne(BIConnection::class);
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function mollie_connections(): HasMany
    {
        return $this->hasMany(MollieConnection::class);
    }

    /**
     * @return HasMany
     */
    public function physical_card_types(): HasMany
    {
        return $this->hasMany(PhysicalCardType::class);
    }

    /**
     * @return HasOne
     * @noinspection PhpUnused
     */
    public function mollie_connection(): HasOne
    {
        return $this
            ->hasOne(MollieConnection::class)
            ->has('active_token')
            ->where('connection_state', MollieConnection::STATE_ACTIVE);
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function fund_providers_allowed_extra_payments(): HasMany
    {
        return $this
            ->hasMany(FundProvider::class)
            ->where('allow_extra_payments', true);
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function fund_providers_allowed_extra_payments_full(): HasMany
    {
        return $this
            ->hasMany(FundProvider::class)
            ->where('allow_extra_payments', true)
            ->where('allow_extra_payments_full', true);
    }

    /**
     * @param bool $fresh
     * @return bool
     */
    public function canUseExtraPaymentsAsProvider(bool $fresh = false): bool
    {
        if ($fresh) {
            return $this->fund_providers_allowed_extra_payments()->exists();
        }

        return $this->fund_providers_allowed_extra_payments->isNotEmpty();
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function canViewExtraPaymentsAsProvider(): bool
    {
        return $this->canUseExtraPaymentsAsProvider() || $this->mollie_connection;
    }

    /**
     * @return bool
     */
    public function canReceiveExtraPayments(): bool
    {
        return
            $this->canUseExtraPaymentsAsProvider() &&
            $this->mollie_connection?->onboardingComplete();
    }

    /**
     * @param string|array $role
     * @return EloquentBuilder|Relation
     */
    public function employeesOfRoleQuery(string|array $role): EloquentBuilder|Relation
    {
        return EmployeeQuery::whereHasRoleFilter($this->employees(), $role);
    }

    /**
     * @param string|array $permission
     * @return EloquentBuilder|Relation
     */
    public function employeesWithPermissionsQuery(string|array $permission): EloquentBuilder|Relation
    {
        return EmployeeQuery::whereHasPermissionFilter($this->employees(), $permission);
    }

    /**
     * @param array|int $fund_id
     * @return EloquentBuilder
     */
    public function providerProductsQuery(mixed $fund_id = []): EloquentBuilder
    {
        $productsQuery = ProductQuery::whereNotExpired($this->products()->getQuery());

        return ProductQuery::whereFundNotExcludedOrHasHistory($productsQuery, $fund_id);
    }

    /**
     * @param string|array $permission
     * @return Collection|Employee[]
     */
    public function employeesWithPermissions(string|array $permission): Collection|Arrayable
    {
        return $this->employeesWithPermissionsQuery($permission)->get();
    }

    /**
     * @param Identity $identity
     * @return EloquentBuilder
     */
    public function identityPermissionsQuery(Identity $identity): EloquentBuilder
    {
        return Permission::whereRelation('roles.employees', [
            'organization_id' => $this->id,
            'identity_address' => $identity->address,
        ]);
    }

    /**
     * Returns identity organization permissions.
     * @param ?Identity $identity
     * @return Collection
     */
    public function identityPermissions(?Identity $identity): SupportCollection
    {
        if ($identity && strcmp($identity->address, $this->identity_address) === 0) {
            return Permission::allMemCached();
        }

        return $identity ? $this->identityPermissionsQuery($identity)->get() : collect();
    }

    /**
     * Check if identity is organization employee.
     * @param Identity $identity
     * @param bool $fresh
     * @return bool
     */
    public function isEmployee(Identity $identity, bool $fresh = true): bool
    {
        if (!$fresh) {
            return $this->employees->where('identity_address', $identity->address)->isNotEmpty();
        }

        return $this->employees()->where('identity_address', $identity->address)->exists();
    }

    /**
     * @param Identity|null $identity
     * @param array|string $permissions
     * @param bool $all
     * @return bool
     */
    public function identityCan(
        Identity $identity = null,
        array|string $permissions = [],
        bool $all = true
    ): bool {
        // convert string to array
        $permissions = (array) $permissions;

        if (!$identity || !$identity->exists || !$identity->address || count($permissions) === 0) {
            return false;
        }

        // as owner of the organization you don't have any restrictions
        if ($identity->address === $this->identity_address) {
            return true;
        }

        // retrieving the list of all the permissions that the identity has
        $permissionKeys = $this->identityPermissions($identity)->pluck('key');
        $permissionsCount = $permissionKeys->intersect($permissions)->count();

        return $all ? $permissionsCount === count($permissions) : $permissionsCount > 0;
    }

    /**
     * @param $identityAddress string
     * @param string|array|bool $permissions
     * @return EloquentBuilder
     */
    public static function queryByIdentityPermissions(
        string $identityAddress,
        string|array|bool $permissions = false
    ): EloquentBuilder {
        $permissions = $permissions === false ? false : (array) $permissions;

        /**
         * Query all the organizations where identity_address has permissions
         * or is the creator.
         */
        return self::query()->where(static function (EloquentBuilder $builder) use (
            $identityAddress,
            $permissions
        ) {
            return $builder->whereIn('id', function (Builder $query) use (
                $identityAddress,
                $permissions
            ) {
                $query->select(['organization_id'])->from((new Employee())->getTable())->where([
                    'identity_address' => $identityAddress,
                ])->whereNull('deleted_at')->whereIn('id', function (Builder $query) use ($permissions) {
                    $query->select('employee_id')->from(
                        (new EmployeeRole())->getTable()
                    )->whereIn('role_id', function (Builder $query) use ($permissions) {
                        $query->select(['id'])->from((new Role())->getTable())->whereIn('id', function (
                            Builder $query
                        ) use ($permissions) {
                            return $query->select(['role_id'])->from(
                                (new RolePermission())->getTable()
                            )->whereIn('permission_id', function (Builder $query) use ($permissions) {
                                $query->select('id')->from((new Permission())->getTable());

                                // allow any permission
                                if ($permissions !== false) {
                                    $query->whereIn('key', $permissions);
                                }

                                return $query;
                            });
                        })->get();
                    });
                });
            })->orWhere('identity_address', $identityAddress);
        });
    }

    /**
     * @param string $identity_address
     * @return Employee|Model|null
     */
    public function findEmployee(string $identity_address): Employee|Model|null
    {
        return $this->employees()->where(compact('identity_address'))->first();
    }

    /**
     * @param $fund_id
     * @return Fund|Model|null
     */
    public function findFund($fund_id = null): Fund|Model|null
    {
        return $this->funds()->where('funds.id', $fund_id)->first();
    }

    /**
     * @param Identity $identity
     * @return Profile
     */
    public function findOrMakeProfile(Identity $identity): Profile
    {
        return $identity->profiles()->where([
            'organization_id' => $this->id,
        ])->firstOrCreate([
            'organization_id' => $this->id,
        ]);
    }

    /**
     * @param Organization $sponsor
     * @param array $options
     * @return EloquentBuilder
     */
    public static function searchProviderOrganizations(
        Organization $sponsor,
        array $options
    ): EloquentBuilder {
        $postcodes = array_get($options, 'postcodes');
        $providerIds = array_get($options, 'provider_ids');
        $businessTypeIds = array_get($options, 'business_type_ids');

        /** @var Carbon|null $dateFrom */
        $dateFrom = array_get($options, 'date_from');
        /** @var Carbon|null $dateTo */
        $dateTo = array_get($options, 'date_to');

        $query = OrganizationQuery::whereIsProviderOrganization(self::query(), $sponsor);

        if ($providerIds) {
            $query->whereIn('id', $providerIds);
        }

        if ($postcodes) {
            $query->whereHas('offices', static function (EloquentBuilder $builder) use ($postcodes) {
                $builder->whereIn('postcode_number', (array) $postcodes);
            });
        }

        if ($businessTypeIds) {
            $query->whereIn('business_type_id', $businessTypeIds);
        }

        if ($dateFrom && $dateTo) {
            $query->whereHas('voucher_transactions', function (EloquentBuilder $builder) use ($dateFrom, $dateTo) {
                $builder->where('created_at', '>=', $dateFrom->clone()->startOfDay());
                $builder->where('created_at', '<=', $dateTo->clone()->endOfDay());
            });
        }

        $queryTransactions = (new FinancialStatisticQueries())->getFilterTransactionsQuery($sponsor, $options);
        $queryTransactions->whereColumn('organization_id', 'organizations.id');

        $query->addSelect([
            'total_spent' => (clone $queryTransactions)->selectRaw('sum(`amount`)'),
            'highest_transaction' => (clone $queryTransactions)->selectRaw('max(`amount`)'),
            'nr_transactions' => (clone $queryTransactions)->selectRaw('count(`id`)'),
        ])->orderByDesc('total_spent');

        return $query;
    }

    /**
     * @return array
     */
    public function productsMeta(): array
    {
        return [
            'total_archived' => Product::onlyTrashed()->where('organization_id', $this->id)->count(),
            'total_provider' => $this->products()->whereNull('sponsor_organization_id')->count(),
            'total_sponsor' => $this->products()->whereNotNull('sponsor_organization_id')->count(),
        ];
    }

    /**
     * @param Bank $bank
     * @param Employee $employee
     * @param Implementation $implementation
     * @return BankConnection|Model
     */
    public function makeBankConnection(
        Bank $bank,
        Employee $employee,
        Implementation $implementation,
    ): BankConnection|Model {
        return BankConnection::addConnection($bank, $employee, $this, $implementation);
    }

    /**
     * @param Identity $identity
     * @param array $roles
     * @param int|null $office_id
     * @return Employee
     */
    public function addEmployee(Identity $identity, array $roles = [], int $office_id = null): Employee
    {
        /** @var Employee $employee */
        $employee = $this->employees()->firstOrCreate([
            'identity_address' => $identity->address,
        ], [
            'office_id' => $office_id,
        ]);

        $employee->roles()->sync($roles);
        EmployeeCreated::dispatch($employee);

        return $employee;
    }

    /**
     * @param Identity $identity
     * @return bool
     */
    public function isOwner(Identity $identity): bool
    {
        return $this->identity_address === $identity->address;
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function getContact(string $key): ?string
    {
        /** @var OrganizationContact|null $contact */
        $contact = $this->contacts->firstWhere('key', $key);

        return $contact?->value;
    }

    /**
     * @param array $contacts
     * @return array
     */
    public function syncContacts(array $contacts): array
    {
        return Arr::map($contacts, fn (array $contact) => $this->contacts()->updateOrCreate([
            'key' => Arr::get($contact, 'key'),
        ], [
            'value' => Arr::get($contact, 'value'),
        ]));
    }

    /**
     * @param array $fields
     * @return void
     */
    public function syncReservationFields(array $fields): void
    {
        $this->reservation_fields()
            ->whereNotIn('id', array_filter(Arr::pluck($fields, 'id')))
            ->delete();

        foreach ($fields as $order => $item) {
            $this->reservation_fields()->updateOrCreate([
                'id' => Arr::get($item, 'id'),
            ], [
                ...Arr::only($item, ['label', 'type', 'description', 'required', 'fillable_by']),
                'order' => $order,
            ]);
        }
    }

    /**
     * @return bool
     */
    public function hasPayoutFunds(): bool
    {
        $payoutFundsQuery = FundQuery::whereIsInternal($this->funds())
            ->whereRelation('fund_config', 'outcome_type', FundConfig::OUTCOME_TYPE_PAYOUT);

        return $this->allow_payouts && $payoutFundsQuery->exists();
    }

    /**
     * @return bool
     */
    public function hasIConnectApiOin(): bool
    {
        return
            $this->isIconnectApiConfigured() &&
            $this->bsn_enabled &&
            !empty($this->iconnect_target_binding) &&
            !empty($this->iconnect_api_oin) &&
            !empty($this->iconnect_base_url);
    }

    /**
     * @param int $identityId
     * @return Identity|null
     */
    public function findRelatedIdentityOrFail(int $identityId): ?Identity
    {
        return IdentityQuery::relatedToOrganization(Identity::query(), $this->id)->findOrFail($identityId);
    }

    /**
     * @return ReservationField[]|Collection
     */
    public function getReservationFieldsForRequester(): Collection|array
    {
        return $this->reservation_fields->filter(fn (ReservationField $field) => $field->isFillableByRequester())->values();
    }

    /**
     * @return ReservationField[]|Collection
     */
    public function getReservationFieldsForProvider(): Collection|array
    {
        return $this->reservation_fields->filter(fn (ReservationField $field) => $field->isFillableByProvider())->values();
    }

    /**
     * @return bool
     */
    private function isIconnectApiConfigured(): bool
    {
        return
            !empty($this->iconnect_env) &&
            !empty($this->iconnect_key) &&
            !empty($this->iconnect_cert) &&
            !empty($this->iconnect_cert_trust);
    }
}
