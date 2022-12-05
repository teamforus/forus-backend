<?php

namespace App\Models;

use App\Events\Funds\FundArchivedEvent;
use App\Events\Funds\FundEndedEvent;
use App\Events\Funds\FundExpiringEvent;
use App\Events\Funds\FundStartedEvent;
use App\Events\Funds\FundUnArchivedEvent;
use App\Events\Vouchers\VoucherAssigned;
use App\Events\Vouchers\VoucherCreated;
use App\Mail\Forus\FundStatisticsMail;
use App\Models\Traits\HasFaq;
use App\Models\Traits\HasTags;
use App\Scopes\Builders\FundCriteriaQuery;
use App\Scopes\Builders\FundCriteriaValidatorQuery;
use App\Scopes\Builders\FundProviderQuery;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\RecordValidationQuery;
use App\Scopes\Builders\VoucherQuery;
use App\Services\BackofficeApiService\BackofficeApi;
use App\Services\BackofficeApiService\Responses\EligibilityResponse;
use App\Services\BackofficeApiService\Responses\PartnerBsnResponse;
use App\Services\BackofficeApiService\Responses\ResidencyResponse;
use App\Services\EventLogService\Traits\HasDigests;
use App\Services\EventLogService\Traits\HasLogs;
use App\Services\FileService\Models\File;
use App\Services\Forus\Notification\EmailFrom;
use App\Services\IConnectApiService\IConnect;
use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Traits\HasMedia;
use App\Traits\HasMarkdownDescription;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\DB;

/**
 * App\Models\Fund
 *
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string|null $description
 * @property string|null $description_text
 * @property string|null $description_short
 * @property string|null $faq_title
 * @property string $request_btn_text
 * @property string|null $external_link_url
 * @property string $external_link_text
 * @property string|null $type
 * @property string $state
 * @property string $balance
 * @property string $balance_provider
 * @property bool $archived
 * @property bool $public
 * @property bool $criteria_editable_after_start
 * @property string|null $notification_amount
 * @property \Illuminate\Support\Carbon|null $notified_at
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $default_validator_employee_id
 * @property bool $auto_requests_validation
 * @property-read Collection|\App\Models\FundBackofficeLog[] $backoffice_logs
 * @property-read int|null $backoffice_logs_count
 * @property-read Collection|\App\Models\Voucher[] $budget_vouchers
 * @property-read int|null $budget_vouchers_count
 * @property-read Collection|\App\Models\FundCriterion[] $criteria
 * @property-read int|null $criteria_count
 * @property-read \App\Models\Employee|null $default_validator_employee
 * @property-read Collection|\App\Services\EventLogService\Models\Digest[] $digests
 * @property-read int|null $digests_count
 * @property-read Collection|\App\Models\Employee[] $employees
 * @property-read int|null $employees_count
 * @property-read Collection|\App\Models\Employee[] $employees_validator_managers
 * @property-read int|null $employees_validator_managers_count
 * @property-read Collection|\App\Models\Employee[] $employees_validators
 * @property-read int|null $employees_validators_count
 * @property-read Collection|\App\Models\Faq[] $faq
 * @property-read int|null $faq_count
 * @property-read Collection|\App\Models\Product[] $formula_products
 * @property-read int|null $formula_products_count
 * @property-read \App\Models\FundConfig|null $fund_config
 * @property-read Collection|\App\Models\FundConfigRecord[] $fund_config_records
 * @property-read int|null $fund_config_records_count
 * @property-read Collection|\App\Models\FundFormulaProduct[] $fund_formula_products
 * @property-read int|null $fund_formula_products_count
 * @property-read Collection|\App\Models\FundFormula[] $fund_formulas
 * @property-read int|null $fund_formulas_count
 * @property-read Collection|\App\Models\FundLimitMultiplier[] $fund_limit_multipliers
 * @property-read int|null $fund_limit_multipliers_count
 * @property-read Collection|\App\Models\FundRequestRecord[] $fund_request_records
 * @property-read int|null $fund_request_records_count
 * @property-read Collection|\App\Models\FundRequest[] $fund_requests
 * @property-read int|null $fund_requests_count
 * @property-read float $budget_left
 * @property-read float $budget_reserved
 * @property-read float $budget_total
 * @property-read float $budget_used_active_vouchers
 * @property-read float $budget_used
 * @property-read float $budget_validated
 * @property-read string $description_html
 * @property-read bool $is_external
 * @property-read string $type_locale
 * @property-read Media|null $logo
 * @property-read Collection|\App\Services\EventLogService\Models\EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read Collection|Media[] $medias
 * @property-read int|null $medias_count
 * @property-read \App\Models\Organization $organization
 * @property-read Collection|\App\Models\Product[] $products
 * @property-read int|null $products_count
 * @property-read Collection|\App\Models\FundProviderInvitation[] $provider_invitations
 * @property-read int|null $provider_invitations_count
 * @property-read Collection|\App\Models\Organization[] $provider_organizations
 * @property-read int|null $provider_organizations_count
 * @property-read Collection|\App\Models\Organization[] $provider_organizations_approved
 * @property-read int|null $provider_organizations_approved_count
 * @property-read Collection|\App\Models\FundProvider[] $providers
 * @property-read int|null $providers_count
 * @property-read Collection|\App\Models\FundProvider[] $providers_allowed_products
 * @property-read int|null $providers_allowed_products_count
 * @property-read Collection|\App\Models\Tag[] $tags
 * @property-read int|null $tags_count
 * @property-read Collection|\App\Models\Tag[] $tags_provider
 * @property-read int|null $tags_provider_count
 * @property-read Collection|\App\Models\Tag[] $tags_webshop
 * @property-read int|null $tags_webshop_count
 * @property-read Collection|\App\Models\FundTopUpTransaction[] $top_up_transactions
 * @property-read int|null $top_up_transactions_count
 * @property-read Collection|\App\Models\FundTopUp[] $top_ups
 * @property-read int|null $top_ups_count
 * @property-read Collection|\App\Models\VoucherTransaction[] $voucher_transactions
 * @property-read int|null $voucher_transactions_count
 * @property-read Collection|\App\Models\Voucher[] $vouchers
 * @property-read int|null $vouchers_count
 * @method static Builder|Fund newModelQuery()
 * @method static Builder|Fund newQuery()
 * @method static Builder|Fund query()
 * @method static Builder|Fund whereArchived($value)
 * @method static Builder|Fund whereAutoRequestsValidation($value)
 * @method static Builder|Fund whereBalance($value)
 * @method static Builder|Fund whereBalanceProvider($value)
 * @method static Builder|Fund whereCreatedAt($value)
 * @method static Builder|Fund whereCriteriaEditableAfterStart($value)
 * @method static Builder|Fund whereDefaultValidatorEmployeeId($value)
 * @method static Builder|Fund whereDescription($value)
 * @method static Builder|Fund whereDescriptionShort($value)
 * @method static Builder|Fund whereDescriptionText($value)
 * @method static Builder|Fund whereEndDate($value)
 * @method static Builder|Fund whereExternalLinkText($value)
 * @method static Builder|Fund whereExternalLinkUrl($value)
 * @method static Builder|Fund whereFaqTitle($value)
 * @method static Builder|Fund whereId($value)
 * @method static Builder|Fund whereName($value)
 * @method static Builder|Fund whereNotificationAmount($value)
 * @method static Builder|Fund whereNotifiedAt($value)
 * @method static Builder|Fund whereOrganizationId($value)
 * @method static Builder|Fund wherePublic($value)
 * @method static Builder|Fund whereRequestBtnText($value)
 * @method static Builder|Fund whereStartDate($value)
 * @method static Builder|Fund whereState($value)
 * @method static Builder|Fund whereType($value)
 * @method static Builder|Fund whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Fund extends BaseModel
{
    use HasMedia, HasTags, HasLogs, HasDigests, HasMarkdownDescription, HasFaq;

    public const EVENT_CREATED = 'created';
    public const EVENT_PROVIDER_APPLIED = 'provider_applied';
    public const EVENT_PROVIDER_REPLIED = 'provider_replied';
    public const EVENT_PROVIDER_APPROVED_PRODUCTS = 'provider_approved_products';
    public const EVENT_PROVIDER_APPROVED_BUDGET = 'provider_approved_budget';
    public const EVENT_PROVIDER_REVOKED_PRODUCTS = 'provider_revoked_products';
    public const EVENT_PROVIDER_REVOKED_BUDGET = 'provider_revoked_budget';
    public const EVENT_BALANCE_LOW = 'balance_low';
    public const EVENT_BALANCE_SUPPLIED = 'balance_supplied';
    public const EVENT_FUND_STARTED = 'fund_started';
    public const EVENT_FUND_ENDED = 'fund_ended';
    public const EVENT_PRODUCT_ADDED = 'fund_product_added';
    public const EVENT_PRODUCT_APPROVED = 'fund_product_approved';
    public const EVENT_PRODUCT_REVOKED = 'fund_product_revoked';
    public const EVENT_PRODUCT_SUBSIDY_REMOVED = 'fund_product_subsidy_removed';
    public const EVENT_FUND_EXPIRING = 'fund_expiring';
    public const EVENT_ARCHIVED = 'archived';
    public const EVENT_UNARCHIVED = 'unarchived';
    public const EVENT_BALANCE_UPDATED_BY_BANK_CONNECTION = 'balance_updated_by_bank_connection';
    public const EVENT_VOUCHERS_EXPORTED = 'vouchers_exported';
    public const EVENT_SPONSOR_NOTIFICATION_CREATED = 'sponsor_notification_created';

    public const STATE_ACTIVE = 'active';
    public const STATE_CLOSED = 'closed';
    public const STATE_PAUSED = 'paused';
    public const STATE_WAITING = 'waiting';

    public const BALANCE_PROVIDER_TOP_UPS = 'top_ups';
    public const BALANCE_PROVIDER_BANK_CONNECTION = 'bank_connection_balance';

    public const STATES = [
        self::STATE_ACTIVE,
        self::STATE_CLOSED,
        self::STATE_PAUSED,
        self::STATE_WAITING,
    ];

    public const TYPE_BUDGET = 'budget';
    public const TYPE_SUBSIDIES = 'subsidies';
    public const TYPE_EXTERNAL = 'external';

    public const TYPES = [
        self::TYPE_BUDGET,
        self::TYPE_SUBSIDIES,
        self::TYPE_EXTERNAL,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organization_id', 'state', 'name', 'description', 'description_text', 'start_date',
        'end_date', 'notification_amount', 'fund_id', 'notified_at', 'public',
        'default_validator_employee_id', 'auto_requests_validation',
        'criteria_editable_after_start', 'type', 'archived', 'description_short',
        'request_btn_text', 'external_link_text', 'external_link_url', 'faq_title',
        'balance',
    ];

    protected $hidden = [
        'fund_config', 'fund_formulas'
    ];

    protected $casts = [
        'public' => 'boolean',
        'archived' => 'boolean',
        'auto_requests_validation' => 'boolean',
        'criteria_editable_after_start' => 'boolean',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'start_date',
        'end_date',
        'notified_at',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'fund_products');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function criteria(): HasMany
    {
        return $this->hasMany(FundCriterion::class);
    }

    /**
     * Get fund logo
     * @return MorphOne
     */
    public function logo(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')->where([
            'type' => 'fund_logo'
        ]);
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
    public function budget_vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class)->whereNull('product_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function voucher_transactions(): HasManyThrough
    {
        return $this->hasManyThrough(VoucherTransaction::class, Voucher::class)
            ->whereIn('target', VoucherTransaction::TARGETS_OUTGOING);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function providers(): HasMany
    {
        return $this->hasMany(FundProvider::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function provider_invitations(): HasMany
    {
        return $this->hasMany(FundProviderInvitation::class, 'from_fund_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function providers_allowed_products(): HasMany
    {
        return $this->hasMany(FundProvider::class)->where([
            'allow_products' => true
        ]);
    }

    /**
     * @return HasMany
     */
    public function backoffice_logs(): HasMany
    {
        return $this->hasMany(FundBackofficeLog::class);
    }

    /**
     * @return MorphToMany
     * @noinspection PhpUnused
     */
    public function tags_webshop(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')
            ->where('scope', 'webshop');
    }

    /**
     * @return MorphToMany
     * @noinspection PhpUnused
     */
    public function tags_provider(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')
            ->where('scope', 'provider');
    }

    /**
     * @return $this
     */
    public function archive(Employee $employee): self
    {
        FundArchivedEvent::dispatch($this->updateModel([
            'archived' => true,
        ]), $employee);

        return $this;
    }

    /**
     * @return $this
     */
    public function unArchive(Employee $employee): self
    {
        FundUnArchivedEvent::dispatch($this->updateModel([
            'archived' => false,
        ]), $employee);

        return $this;
    }

    /**
     * @param array $attributes
     * @return void
     */
    public function makeFundConfig(array $attributes = []): void
    {
        if ($this->fund_config()->exists()) {
            return;
        }

        $preApprove = $this->isExternal() && $this->organization->pre_approve_external_funds;

        $this->fund_config()->create()->forceFill($preApprove ? [
            'key' => str_slug($this->name, '_') . '_' . resolve('token_generator')->generate(8),
            'is_configured' => 1,
            'implementation_id' => $this->organization->implementations[0]->id ?? null,
        ] : [])->save();

        $this->updateFundsConfig($attributes);
    }

    /**
     * @param array $attributes
     * @return void
     */
    public function updateFundsConfig(array $attributes): void
    {
        $values = array_only($attributes, [
            'allow_fund_requests', 'allow_prevalidations', 'allow_direct_requests',
            'email_required', 'contact_info_enabled', 'contact_info_required',
            'contact_info_message_custom', 'contact_info_message_text',
        ]);

        $replaceValues = $this->isExternal() ? array_fill_keys([
            'allow_fund_requests', 'allow_prevalidations', 'allow_direct_requests',
        ], false) : [];

        $this->fund_config->forceFill(array_merge($values, $replaceValues))->save();
    }

    /**
     * @param array $tagIds
     * @param string $scope
     * @return void
     */
    public function syncTags(array $tagIds, string $scope = 'webshop'): void
    {
        $query = Tag::query();

        // Target tags of scope
        $query->where(function(Builder $builder) use ($tagIds, $scope) {
            $builder->whereIn('id', $tagIds);
            $builder->whereIn('scope', (array) $scope);
        });

        // Tags to keep from other scopes
        $query->orWhere(function(Builder $builder) use ($scope) {
            $otherScopeTags = $this->tags()->whereNotIn('scope', (array) $scope);
            $builder->whereIn('id', $otherScopeTags->select('tags.id')->getQuery());
        });

        $this->tags_webshop()->sync($query->pluck('id'));
    }

    /**
     * @param array|null $tagIds
     * @param string $scope
     * @return void
     */
    public function syncTagsOptional(?array $tagIds = null, string $scope = 'webshop'): void
    {
        if (!is_null($tagIds)) {
            $this->syncTags($tagIds, $scope);
        }
    }

    /**
     * @param string $balance
     * @param BankConnection $bankConnection
     * @return $this
     */
    public function setBalance(string $balance, BankConnection $bankConnection): self
    {
        $this->update(compact('balance'));

        $this->log(static::EVENT_BALANCE_UPDATED_BY_BANK_CONNECTION, [
            'bank_connection' => $bankConnection,
            'bank_connection_account' => $bankConnection->bank_connection_default_account,
        ], [
            'fund_balance' => $this->balance,
            'fund_balance_provider' => $this->balance_provider,
        ]);

        return $this;
    }

    /**
     * @param bool|null $withBalance
     * @param bool|null $withEmail
     * @return Builder|Identity
     */
    public function activeIdentityQuery(
        bool $withBalance = false,
        ?bool $withEmail = null
    ): Builder|Identity {
        $builder = Identity::whereHas('vouchers', function(Builder $builder) use ($withBalance) {
            VoucherQuery::whereNotExpiredAndActive($builder->where([
                'fund_id' => $this->id,
            ]));

            if ($withBalance) {
                VoucherQuery::whereHasBalance($builder);
            }
        });

        if ($withEmail === true) {
            $builder->whereHas('primary_email');
        }

        if ($withEmail === false) {
            $builder->whereDoesntHave('primary_email');
        }

        return $builder;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private function providers_declined_products(): HasMany
    {
        return $this->hasMany(FundProvider::class)->where([
            'allow_products' => false
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function top_ups(): HasMany
    {
        return $this->hasMany(FundTopUp::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function default_validator_employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'default_validator_employee_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     * @noinspection PhpUnused
     */
    public function top_up_transactions(): HasManyThrough
    {
        return $this->hasManyThrough(FundTopUpTransaction::class, FundTopUp::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fund_requests(): HasMany
    {
        return $this->hasMany(FundRequest::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     * @noinspection PhpUnused
     */
    public function fund_request_records(): HasManyThrough
    {
        return $this->hasManyThrough(FundRequestRecord::class, FundRequest::class);
    }

    /**
     * @return bool
     */
    public function isExternal(): bool
    {
        return $this->type === static::TYPE_EXTERNAL;
    }

    /**
     * @return bool
     */
    public function isInternal(): bool
    {
        return $this->type !== static::TYPE_EXTERNAL;
    }

    /**
     * @return bool
     */
    public function isConfigured(): bool
    {
        return $this->fund_config->is_configured ?? false;
    }

    /**
     * @return float
     * @noinspection PhpUnused
     */
    public function getBudgetValidatedAttribute(): float
    {
        return 0;
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getTypeLocaleAttribute(): string
    {
        return [
            self::TYPE_SUBSIDIES => 'Acties',
            self::TYPE_EXTERNAL => 'External',
            self::TYPE_BUDGET => 'Budget',
        ][$this->type] ?? $this->type;
    }

    /**
     * @return float
     * @noinspection PhpUnused
     */
    public function getBudgetTotalAttribute(): float
    {
        if ($this->balance_provider === static::BALANCE_PROVIDER_TOP_UPS) {
            return round($this->top_up_transactions->sum('amount'), 2);
        }

        if ($this->balance_provider === static::BALANCE_PROVIDER_BANK_CONNECTION) {
            return round(floatval($this->balance) + $this->budget_used, 2);
        }

        return 0;
    }

    /**
     * @return float
     * @noinspection PhpUnused
     */
    public function getBudgetUsedAttribute(): float
    {
        return round($this->voucher_transactions()->sum('voucher_transactions.amount'), 2);
    }

    /**
     * @return float
     * @noinspection PhpUnused
     */
    public function getBudgetUsedActiveVouchersAttribute(): float
    {
        return round($this->voucher_transactions()
            ->where('vouchers.expire_at', '>', now())
            ->sum('voucher_transactions.amount'), 2);
    }

    /**
     * @return float
     * @noinspection PhpUnused
     */
    public function getBudgetLeftAttribute(): float
    {
        if ($this->balance_provider === static::BALANCE_PROVIDER_TOP_UPS) {
            return round($this->budget_total - $this->budget_used, 2);
        }

        if ($this->balance_provider === static::BALANCE_PROVIDER_BANK_CONNECTION) {
            return round($this->balance, 2);
        }

        return 0;
    }

    /**
     * @return float
     * @noinspection PhpUnused
     */
    public function getBudgetReservedAttribute(): float
    {
        return round($this->budget_vouchers()->sum('amount'), 2);
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function getIsExternalAttribute(): bool
    {
        return $this->isExternal();
    }

    /**
     * @return float
     */
    public function getTransactionCosts(): float
    {
        return $this->voucher_transactions()->where(function(Builder $builder) {
            $builder->where('voucher_transactions.amount', '>', 0);
        })->count() * 0.10;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     * @noinspection PhpUnused
     */
    public function provider_organizations_approved(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'fund_providers')->where([
            'state' => FundProvider::STATE_ACCEPTED,
        ])->where(static function(Builder $builder) {
            $builder->where('allow_budget', true);
            $builder->orWhere('allow_products', true);
            $builder->orWhere('allow_some_products', true);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     * @noinspection PhpUnused
     */
    public function provider_organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'fund_providers');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     * @noinspection PhpUnused
     */
    public function employees(): HasManyThrough
    {
        return $this->hasManyThrough(
            Employee::class,
            Organization::class,
            'id',
            'organization_id',
            'organization_id',
            'id'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     * @noinspection PhpUnused
     */
    public function employees_validators(): HasManyThrough
    {
        return $this->hasManyThrough(
            Employee::class,
            Organization::class,
            'id',
            'organization_id',
            'organization_id',
            'id'
        )->whereHas('roles.permissions', static function(Builder $builder) {
            $builder->where('key', 'validate_records');
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     * @noinspection PhpUnused
     */
    public function employees_validator_managers(): HasManyThrough
    {
        return $this->hasManyThrough(
            Employee::class,
            Organization::class,
            'id',
            'organization_id',
            'organization_id',
            'id'
        )->whereHas('roles.permissions', static function(Builder $builder) {
            $builder->where('key', 'manage_validators');
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     * @noinspection PhpUnused
     */
    public function fund_config(): HasOne
    {
        return $this->hasOne(FundConfig::class);
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function fund_config_records(): HasMany
    {
        return $this->hasMany(FundConfigRecord::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function fund_formulas(): HasMany
    {
        return $this->hasMany(FundFormula::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function fund_limit_multipliers(): HasMany
    {
        return $this->hasMany(FundLimitMultiplier::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fund_formula_products(): HasMany
    {
        return $this->hasMany(FundFormulaProduct::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     * @noinspection PhpUnused
     */
    public function formula_products(): HasManyThrough
    {
        return $this->hasManyThrough(
            Product::class,
            FundFormulaProduct::class,
            'fund_id',
            'id'
        );
    }

    /**
     * @param string $identity_address
     * @param string $record_type
     * @param FundCriterion|null $criterion
     * @return Model|Record|null
     */
    public function getTrustedRecordOfType(
        string $identity_address,
        string $record_type,
        FundCriterion $criterion = null
    ): Record|Model|null {
        $fund = $this;
        $daysTrusted = $this->getTrustedDays($record_type);

        $builder = Record::search(Identity::findByAddress($identity_address)->records(), [
            'type' => $record_type,
        ])->whereHas('validations', function(Builder $query) use ($daysTrusted, $fund, $criterion) {
            RecordValidationQuery::whereStillTrustedQuery($query, $daysTrusted);
            RecordValidationQuery::whereTrustedByQuery($query, $fund, $criterion);
        });

        $validationSubQuery = RecordValidation::where(function(Builder $query) use ($daysTrusted, $fund, $criterion) {
            $query->whereColumn('records.id', '=', 'record_validations.record_id');
            RecordValidationQuery::whereStillTrustedQuery($query, $daysTrusted);
            RecordValidationQuery::whereTrustedByQuery($query, $fund, $criterion);
        });

        $builder->addSelect([
            'validated_at_validation' => (clone $validationSubQuery)->select('created_at'),
            'validated_at_prevalidation' => (clone $validationSubQuery)->select([
                'validated_at_prevalidation' => Prevalidation::whereColumn([
                    'prevalidations.id' => 'record_validations.prevalidation_id',
                ])->select('prevalidations.validated_at')
            ])
        ]);

        $builder = Record::fromSub($builder->getQuery(), 'records')->select([
            '*',
            DB::raw('IF(`validated_at_prevalidation`, `validated_at_prevalidation`, `validated_at_validation`) as `validated_at`'),
        ])->orderByDesc('validated_at');

        return $builder->first();
    }

    /**
     * @param string $recordType
     * @return int|null
     */
    public function getTrustedDays(string $recordType): ?int
    {
        /** @var FundConfigRecord $typeConfig */
        $typeConfig = $this->fund_config_records->where('record_type', $recordType)->first();
        $typeConfigValue = $typeConfig->record_validity_days ?? null;
        $fundConfigValue = $this->fund_config->record_validity_days ?? null;

        if ($typeConfigValue === 0) {
            return $typeConfigValue;
        }

        if ($typeConfigValue === null && $fundConfigValue === 0) {
            return $fundConfigValue;
        }

        return ($typeConfigValue ?: false) ?:
            ($fundConfigValue ?: false) ?:
                ((int) config('forus.funds.record_validity_days')) ?: null;
    }

    /**
     * @param string|null $identityAddress
     * @return int
     */
    public function amountForIdentity(?string $identityAddress): int
    {
        if ($this->fund_formulas->count() === 0 &&
            $this->fund_formula_products->pluck('price')->sum() === 0) {
            return 0;
        }

        return $this->fund_formulas->map(function(FundFormula $formula) use ($identityAddress) {
            switch ($formula->type) {
                case 'fixed': return $formula->amount;
                case 'multiply': {
                    $record = $this->getTrustedRecordOfType(
                        $identityAddress,
                        $formula->record_type_key
                    );

                    return is_numeric($record?->value) ? $formula->amount * $record->value : 0;
                }
                default: return 0;
            }
        })->sum() + $this->fund_formula_products->pluck('price')->sum();
    }

    /**
     * @param string|null $identityAddress
     * @return int
     */
    public function multiplierForIdentity(?string $identityAddress): int {
        /** @var FundLimitMultiplier[]|Collection $multipliers */
        $multipliers = $this->fund_limit_multipliers()->get();

        if (!$identityAddress || ($multipliers->count() === 0)) {
            return 1;
        }

        return $multipliers->map(function(FundLimitMultiplier $multiplier) use ($identityAddress) {
            $record = $this->getTrustedRecordOfType(
                $identityAddress,
                $multiplier->record_type_key
            );

            return ((int) ($record ? $record->value: 1)) * $multiplier->multiplier;
        })->sum();
    }

    /**
     * @param array $options
     * @param Builder|null $query
     * @return Builder
     */
    public static function search(array $options, Builder $query = null): Builder
    {
        $query = $query ?: self::query();

        if (!array_get($options, 'with_archived', false)) {
            $query->where('archived', false);
        }

        if (!array_get($options, 'with_external', false)) {
            $query->where('type', '!=', self::TYPE_EXTERNAL);
        }

        if (array_get($options, 'configured', false)) {
            FundQuery::whereIsConfiguredByForus($query);
        }

        if ($tag = array_get($options, 'tag')) {
            $query->whereHas('tags_provider', static function(Builder $query) use ($tag) {
                $query->where('key', $tag);
            });
        }

        if ($tag_id = array_get($options, 'tag_id')) {
            $query->whereHas('tags_webshop', static function(Builder $query) use ($tag_id) {
                $query->where('tags.id', $tag_id);
            });
        }

        if ($organization_id = array_get($options, 'organization_id')) {
            $query->where('organization_id', $organization_id);
        }

        if ($fund_id = array_get($options, 'fund_id')) {
            $query->where('id', $fund_id);
        }

        if ($q = array_get($options, 'q')) {
            $query = FundQuery::whereQueryFilter($query, $q);
        }

        if ($implementation_id = array_get($options, 'implementation_id')) {
            $query = FundQuery::whereImplementationIdFilter($query, $implementation_id);
        }

        return $query->orderBy(
            array_get($options, 'order_by', 'created_at'),
            array_get($options, 'order_by_dir', 'asc')
        );
    }

    /**
     * @return mixed|null
     */
    public function amountFixedByFormula()
    {
        if (!$fundFormula = $this->fund_formulas) {
            return null;
        }

        if ($fundFormula->filter(static function (FundFormula $formula){
            return $formula->type !== 'fixed';
        })->count()) {
            return null;
        }

        return $fundFormula->sum('amount');
    }

    /**
     * @return Fund[]|Builder[]|Collection|\Illuminate\Support\Collection
     * @noinspection PhpUnused
     */
    public static function configuredFunds() {
        try {
            return static::query()->whereHas('fund_config')->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function requiredPrevalidationKeys(): \Illuminate\Support\Collection {
        return collect(collect()->merge(
            $this->fund_config ? [$this->fund_config->csv_primary_key] : []
        )->merge(
            $this->fund_formulas->where('type', 'multiply')->pluck('record_type_key')
        )->merge(
            $this->criteria->pluck('record_type_key')
        ))->unique();
    }

    /**
     * Change fund state
     *
     * @param string $state
     * @return $this
     */
    public function changeState(string $state): self {
        if (in_array($state, self::STATES)) {
            $this->update(compact('state'));
        }

        return $this;
    }

    /**
     * Update fund state by the start and end dates
     */
    public static function checkStateQueue(): void {
        $funds = static::where(function(Builder $builder) {
            FundQuery::whereIsConfiguredByForus($builder);
        })->whereDate('start_date', '<=', now())->get();

        foreach ($funds as $fund) {
            if ($fund->isPaused() && $fund->start_date->startOfDay()->isPast()) {
                FundStartedEvent::dispatch($fund->changeState(self::STATE_ACTIVE));
            }

            $expirationNotified = $fund->logs()->where('event', self::EVENT_FUND_EXPIRING)->exists();
            $isTimeToNotify = $fund->end_date->clone()->subDays(14)->isPast();

            if (!$expirationNotified && !$fund->isClosed() && $isTimeToNotify) {
                FundExpiringEvent::dispatch($fund);
            }

            if (!$fund->isClosed() && $fund->end_date->clone()->addDay()->isPast()) {
                FundEndedEvent::dispatch($fund->changeState(self::STATE_CLOSED));
            }
        }
    }

    /**
     * Send funds user count statistic to email
     * @param string $email
     * @return void
     */
    public static function sendUserStatisticsReport(string $email): void {
        $funds = self::whereHas('fund_config', static function (Builder $query) {
            return $query->where('is_configured', true);
        })->whereIn('state', [
            self::STATE_ACTIVE, self::STATE_PAUSED
        ])->where('type', '!=', self::TYPE_EXTERNAL)->get();

        foreach ($funds as $fund) {
            $organization = $fund->organization;
            $sponsorCount = $organization->employees->count();

            $providersQuery = FundProvider::query();
            $providersQuery = FundProviderQuery::whereApprovedForFundsFilter($providersQuery, $fund->id);

            $providerCount = $providersQuery->get()->map(function (FundProvider $fundProvider){
                return $fundProvider->organization->employees->count();
            })->sum();

            if ($fund->state === self::STATE_ACTIVE) {
                $requesterCount = $fund->vouchers()->whereNull('parent_id')->count();
            } else {
                $requesterCount = 0;
            }

            resolve('forus.services.notification')->sendSystemMail($email, new FundStatisticsMail([
                'fund_name' => $fund->name,
                'sponsor_name' => $organization->name,
                'sponsor_count' => $sponsorCount,
                'provider_count' => $providerCount,
                'request_count' => $requesterCount,
                'total_count' => $sponsorCount + $providerCount + $requesterCount,
            ], $fund->getEmailFrom()));
        }
    }

    /**
     * @param string|null $identity_address
     * @param array $extraFields
     * @param float|null $voucherAmount
     * @param Carbon|null $expire_at
     * @param int|null $limit_multiplier
     * @return Voucher|null
     */
    public function makeVoucher(
        string $identity_address = null,
        array $extraFields = [],
        float $voucherAmount = null,
        Carbon $expire_at = null,
        ?int $limit_multiplier = null
    ): ?Voucher {
        $amount = $voucherAmount ?: $this->amountForIdentity($identity_address);
        $returnable = false;
        $expire_at = $expire_at ?: $this->end_date;
        $fund_id = $this->id;
        $limit_multiplier = $limit_multiplier ?: $this->multiplierForIdentity($identity_address);
        $voucher = null;

        if ($this->fund_formulas->count() > 0) {
            $voucher = Voucher::create(array_merge(compact(
                'identity_address', 'amount', 'expire_at', 'fund_id',
                'returnable', 'limit_multiplier'
            ), $extraFields));

            VoucherCreated::dispatch($voucher);
        }

        return $voucher;
    }

    /**
     * @param string|null $identity_address
     * @param array $extraFields
     * @param Carbon|null $expireAt
     * @return array|Voucher[]
     */
    public function makeFundFormulaProductVouchers(
        string $identity_address = null,
        array $extraFields = [],
        Carbon $expireAt = null
    ): array {
        $vouchers = [];
        $fundEndDate = $this->end_date;

        if ($this->fund_formula_products->count() > 0) {
            foreach ($this->fund_formula_products as $fund_formula_product) {
                $productExpireDate = $fund_formula_product->product->expire_at;
                $voucherExpireAt = $productExpireDate && $fundEndDate->gt($productExpireDate) ? $productExpireDate : $fundEndDate;
                $voucherExpireAt = $expireAt && $voucherExpireAt->gt($expireAt) ? $expireAt : $voucherExpireAt;

                $voucher = $this->makeProductVoucher(
                    $identity_address,
                    $extraFields,
                    $fund_formula_product->product->id,
                    $voucherExpireAt,
                    $fund_formula_product->price
                );

                $vouchers[] = $voucher;

                VoucherAssigned::broadcast($voucher);
            }
        }

        return $vouchers;
    }

    /**
     * @param string|null $identity_address
     * @param array $extraFields
     * @param int|null $product_id
     * @param Carbon|null $expire_at
     * @param float|null $price
     * @return Voucher
     */
    public function makeProductVoucher(
        string $identity_address = null,
        array $extraFields = [],
        int $product_id = null,
        Carbon $expire_at = null,
        float $price = null
    ): Voucher {
        $amount = $price ?: Product::findOrFail($product_id)->price;
        $expire_at = $expire_at ?: $this->end_date;
        $fund_id = $this->id;
        $returnable = false;

        $voucher = Voucher::create(array_merge(compact(
            'identity_address', 'amount', 'expire_at',
            'product_id','fund_id', 'returnable'
        ), $extraFields));

        VoucherCreated::dispatch($voucher, false);

        return $voucher;
    }

    /**
     * @return bool
     */
    public function isArchived(): bool
    {
        return $this->archived;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return ($this->state === static::STATE_ACTIVE) && !$this->isExpired();
    }

    /**
     * @return bool
     */
    public function isClosed(): bool
    {
        return $this->state === static::STATE_CLOSED;
    }

    /**
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->end_date->isPast();
    }

    /**
     * @return bool
     */
    public function isWaiting(): bool
    {
        return $this->state === static::STATE_WAITING;
    }

    /**
     * @return bool
     */
    public function isPaused(): bool
    {
        return $this->state === static::STATE_PAUSED;
    }

    /**
     * @param FundCriterion|null $fundCriterion
     * @return array
     */
    public function validatorEmployees(?FundCriterion $fundCriterion = null): array
    {
        $employees = $this->employees_validators()->pluck('employees.identity_address');
        $externalEmployees = [];

        /** @var Organization[] $external_validators */
        $external_validators = $fundCriterion ? (
            $this->organization->external_validators()->whereIn(
                'organizations.id',
                $fundCriterion->external_validator_organizations->pluck(
                    'validator_organization_id'
                )->toArray()
            )->get()
        ) : $this->organization->external_validators;

        foreach ($external_validators as $external_validator) {
            $externalEmployees[] = $external_validator->employeesWithPermissions(
                'validate_records'
            )->pluck('identity_address')->toArray();
        }

        return array_merge($employees->toArray(), array_flatten($externalEmployees, 1));
    }

    /**
     * @param Identity $identity
     * @param array $records
     * @param string|null $contactInformation
     * @return FundRequest
     */
    public function makeFundRequest(
        Identity $identity,
        array $records,
        ?string $contactInformation = null
    ): FundRequest {
        /** @var FundRequest $fundRequest */
        $fundRequest = $this->fund_requests()->create(array_merge([
            'identity_address' => $identity->address,
        ], $this->fund_config->contact_info_enabled ? [
            'contact_information' => $contactInformation,
        ] : []));

        foreach ($records as $record) {
            /** @var FundRequestRecord $requestRecord */
            $requestRecord = $fundRequest->records()->create($record);

            foreach ($record['files'] ?? [] as $fileUid) {
                $requestRecord->attachFile(File::findByUid($fileUid));
            }
        }

        return $fundRequest;
    }

    /**
     * Update criteria for existing fund
     * @param array $criteria
     * @return $this
     */
    public function syncCriteria(array $criteria): self
    {
        // remove criteria not listed in the array
        if ($this->criteriaIsEditable()) {
            $this->criteria()->whereNotIn('id', array_filter(
                array_pluck($criteria, 'id'), static function($id) {
                return !empty($id);
            }))->delete();
        }

        foreach ($criteria as $criterion) {
            $this->syncCriterion($criterion);
        }

        return $this;
    }

    /**
     * Update existing or create new fund criterion
     * @param array $criterion
     */
    protected function syncCriterion(array $criterion): void {
        /** @var FundCriterion $fundCriterion */
        $validators = $criterion['validators'] ?? null;
        $fundCriterion = $this->criteria()->find($criterion['id'] ?? null);

        if (!$fundCriterion && !$this->criteriaIsEditable()) {
            return;
        }

        /** @var FundCriterion|null $db_criteria */
        $data_criterion = array_only($criterion, $this->criteriaIsEditable() ? [
            'record_type_key', 'operator', 'value', 'show_attachment',
            'description', 'title'
        ] : ['show_attachment', 'description', 'title']);

        if ($fundCriterion) {
            $fundCriterion->update($data_criterion);
        } else {
            $fundCriterion = $this->criteria()->create($data_criterion);
        }

        if (is_array($validators)) {
            $this->syncCriterionValidators($fundCriterion, $validators);
        }
    }

    /**
     * Update fund criterion validators
     * @param FundCriterion $criterion
     * @param array $externalValidators
     */
    protected function syncCriterionValidators(
        FundCriterion $criterion,
        array $externalValidators
    ): void {
        $fund = $this;
        $currentValidators = [];

        /** @var OrganizationValidator[] $validators */
        $validators = array_map(static function($organization_validator_id) use ($fund) {
            return $fund->organization->organization_validators()->where([
                'organization_validators.id' => $organization_validator_id
            ])->first();
        }, array_unique(array_values($externalValidators)));

        foreach ($validators as $organizationValidator) {
            $currentValidators[] = $criterion->fund_criterion_validators()->firstOrCreate([
                'organization_validator_id' => $organizationValidator->id
            ], [
                'accepted' => $organizationValidator->validator_organization
                    ->validator_auto_accept_funds
            ])->getKey();
        }

        /** @var FundCriterionValidator[]|Collection $criterionValidators */
        $criterionValidators = $criterion->fund_criterion_validators()->whereNotIn(
            'fund_criterion_validators.id', $currentValidators
        )->get();

        $this->resignCriterionValidators($criterionValidators);

        $criterion->fund_criterion_validators()->whereIn(
            'fund_criterion_validators.id',
            $criterionValidators->pluck('id')->toArray()
        )->delete();
    }

    /**
     * Resign fund request record employees be criterion validator
     * @param FundCriterionValidator[]|Collection $criterionValidators
     */
    protected function resignCriterionValidators($criterionValidators): void {
        foreach ($criterionValidators as $criterionValidator) {
            $validator_organization = $criterionValidator
                ->external_validator->validator_organization;

            foreach ($validator_organization->employees as $employee) {
                /** @var FundRequest[] $fund_requests */
                $fund_requests = $this->fund_requests()->whereIn('state', [
                    FundRequest::STATE_PENDING,
                    FundRequest::STATE_APPROVED_PARTLY,
                ])->get();

                foreach ($fund_requests as $fund_request) {
                    $fund_request->resignEmployee($employee, $criterionValidator->fund_criterion);
                }
            }
        }
    }

    /**
     * @param array $productIds
     * @return $this
     */
    public function updateFormulaProducts(array $productIds): self {
        /** @var Collection|Product[] $products */
        $products = Product::whereIn('id', $productIds)->get();

        $this->fund_formula_products()->whereNotIn(
            'product_id',
            $products->pluck('id')
        )->delete();

        foreach ($products as $product) {
            $where = [
                'product_id' => $product->id
            ];

            if (!$this->fund_formula_products()->where($where)->exists()) {
                $this->fund_formula_products()->create($where)->update([
                    'price' => $product->price
                ]);
            }
        }

        return $this;
    }

    /**
     * @param string $uri
     * @return string
     */
    public function urlWebshop(string $uri = "/"): string
    {
        return $this->fund_config->implementation->urlWebshop($uri);
    }

    /**
     * @param string $uri
     * @return string
     */
    public function urlSponsorDashboard(string $uri = "/"): string
    {
        return $this->fund_config->implementation->urlSponsorDashboard($uri);
    }

    /**
     * @param string $uri
     * @return string
     */
    public function urlProviderDashboard(string $uri = "/"): string
    {
        return $this->fund_config->implementation->urlProviderDashboard($uri);
    }

    /**
     * @param string $uri
     * @return mixed|string
     * @noinspection PhpUnused
     */
    public function urlValidatorDashboard(string $uri = "/"): string
    {
        return $this->fund_config->implementation->urlValidatorDashboard($uri);
    }

    /**
     * @return Implementation
     */
    public function getImplementation(): Implementation
    {
        return $this->fund_config->implementation ?? Implementation::general();
    }

    /**
     * @return \App\Models\FundTopUp
     * @noinspection PhpUnused
     */
    public function getOrCreateTopUp(): FundTopUp
    {
        /** @var FundTopUp $topUp */
        if ($this->top_ups()->count() > 0) {
            $topUp = $this->top_ups()->first();
        } else {
            $topUp = $this->top_ups()->create([
                'code' => FundTopUp::generateCode()
            ]);
        }

        return $topUp;
    }

    /**
     * @return bool
     */
    public function criteriaIsEditable(): bool {
        return ($this->state === self::STATE_WAITING) || (
            ($this->state === self::STATE_ACTIVE) && $this->criteria_editable_after_start);
    }

    /**
     * @param Organization $validatorOrganization
     */
    public function detachExternalValidator(
        Organization $validatorOrganization
    ): void {
        /** @var FundCriterion[] $fundCriteria */
        $fundCriteria = FundCriteriaQuery::whereHasExternalValidatorFilter(
            $this->criteria()->getQuery(),
            $validatorOrganization->id
        )->get();

        // delete validator organization from all fund criteria
        foreach ($fundCriteria as $criterion) {
            FundCriteriaValidatorQuery::whereHasExternalValidatorFilter(
                $criterion->fund_criterion_validators()->getQuery(),
                $validatorOrganization->id
            )->delete();
        }

        /**
          All pending fund requests which have records assigned to external validator employees
         * @var FundRequest[] $fundRequests
         */
        $fundRequests = FundRequest::whereHas('records.employee', static function(
            Builder $builder
        ) use ($validatorOrganization) {
            $builder->where('organization_id', $validatorOrganization->id);
        })->where('state', [
            FundRequest::STATE_PENDING,
            FundRequest::STATE_APPROVED_PARTLY
        ])->get();

        foreach ($fundRequests as $fundRequest) {
            foreach ($validatorOrganization->employees as $employee) {
                $fundRequest->resignEmployee($employee);
            }
        }
    }

    /**
     * @param Builder $vouchersQuery
     * @return array
     */
    public static function getFundDetails(Builder $vouchersQuery) : array
    {
        $vouchersQuery = VoucherQuery::whereNotExpired($vouchersQuery);
        $activeVouchersQuery = VoucherQuery::whereNotExpiredAndActive((clone $vouchersQuery));
        $inactiveVouchersQuery = VoucherQuery::whereNotExpiredAndPending((clone $vouchersQuery));
        $deactivatedVouchersQuery = VoucherQuery::whereNotExpiredAndDeactivated((clone $vouchersQuery));

        $vouchers_count = $vouchersQuery->count();
        $inactive_count = $inactiveVouchersQuery->count();
        $active_count = $activeVouchersQuery->count();
        $deactivated_count = $deactivatedVouchersQuery->count();
        $inactive_percentage = $inactive_count ? $inactive_count / $vouchers_count * 100 : 0;

        return [
            'reserved'              => $activeVouchersQuery->sum('amount'),
            'vouchers_amount'       => $vouchersQuery->sum('amount'),
            'vouchers_count'        => $vouchers_count,
            'active_amount'         => $activeVouchersQuery->sum('amount'),
            'active_count'          => $active_count,
            'inactive_amount'       => $inactiveVouchersQuery->sum('amount'),
            'inactive_count'        => $inactive_count,
            'inactive_percentage'   => currency_format($inactive_percentage),
            'deactivated_amount'    => $deactivatedVouchersQuery->sum('amount'),
            'deactivated_count'     => $deactivated_count,
        ];
    }

    /**
     * @param Collection|Fund[] $funds
     * @return array
     */
    public static function getFundTotals(Collection $funds) : array
    {
        $budget = 0;
        $budget_left = 0;
        $budget_used = 0;
        $budget_used_active_vouchers = 0;
        $transaction_costs = 0;

        $query = Voucher::query()->whereNull('parent_id')->whereIn('fund_id', $funds->pluck('id'));
        $vouchersQuery = VoucherQuery::whereNotExpired($query);
        $activeVouchersQuery = VoucherQuery::whereNotExpiredAndActive((clone $vouchersQuery));
        $inactiveVouchersQuery = VoucherQuery::whereNotExpiredAndPending((clone $vouchersQuery));
        $deactivatedVouchersQuery = VoucherQuery::whereNotExpiredAndDeactivated((clone $vouchersQuery));

        $vouchers_amount = currency_format($vouchersQuery->sum('amount'));
        $active_vouchers_amount = currency_format($activeVouchersQuery->sum('amount'));
        $inactive_vouchers_amount = currency_format($inactiveVouchersQuery->sum('amount'));
        $deactivated_vouchers_amount = currency_format($deactivatedVouchersQuery->sum('amount'));

        $vouchers_count = $vouchersQuery->count();
        $active_vouchers_count = $activeVouchersQuery->count();
        $inactive_vouchers_count = $inactiveVouchersQuery->count();
        $deactivated_vouchers_count = $deactivatedVouchersQuery->count();

        foreach ($funds as $fund) {
            $budget += $fund->budget_total;
            $budget_left += $fund->budget_left;
            $budget_used += $fund->budget_used;
            $budget_used_active_vouchers += $fund->budget_used_active_vouchers;
            $transaction_costs += $fund->getTransactionCosts();
        }

        return compact(
            'budget', 'budget_left',
            'budget_used', 'budget_used_active_vouchers', 'transaction_costs',
            'vouchers_amount', 'vouchers_count', 'active_vouchers_amount', 'active_vouchers_count',
            'inactive_vouchers_amount', 'inactive_vouchers_count',
            'deactivated_vouchers_amount', 'deactivated_vouchers_count'
        );
    }

    /**
     * @return EmailFrom
     */
    public function getEmailFrom(): EmailFrom {
        return $this->fund_config?->implementation->getEmailFrom() ??
            EmailFrom::createDefault();
    }

    /**
     * @return bool
     */
    public function isTypeSubsidy(): bool {
        return $this->type === $this::TYPE_SUBSIDIES;
    }

    /**
     * @return bool
     */
    public function isTypeBudget(): bool {
        return $this->type === $this::TYPE_BUDGET;
    }

    /**
     * @return bool
     */
    public function isHashingBsn(): bool {
        return $this->fund_config->hash_bsn;
    }

    /**
     * @param $value
     * @return string|null
     */
    public function getHashedValue($value): ?string {
        if (!$this->isHashingBsn()) {
            return null;
        }

        return hash_hmac('sha256', $value, $this->fund_config->hash_bsn_salt);
    }

    /**
     * @param Identity $identity
     * @return bool
     */
    public function isTakenByPartner(Identity $identity): bool
    {
        return Identity::whereHas('vouchers', function(Builder $builder) {
            return VoucherQuery::whereNotExpired($builder->where('fund_id', $this->id));
        })->whereHas('records', function(Builder $builder) use ($identity) {
            $builder->where(function(Builder $builder) use ($identity) {
                $identityBsn = $identity->bsn;

                $builder->where(function(Builder $builder) use ($identity, $identityBsn) {
                    $builder->whereRelation('record_type', [
                        'record_types.key' => $this->isHashingBsn() ? 'partner_bsn_hash': 'partner_bsn',
                    ]);

                    $builder->whereIn('value', $this->isHashingBsn() ? array_filter([
                        $this->getTrustedRecordOfType($identity->address, 'bsn_hash')?->value ?: null,
                        $identityBsn ? $this->getHashedValue($identityBsn) : null
                    ]) : [$identityBsn ?: null]);
                });

                $builder->orWhere(function(Builder $builder) use ($identity, $identityBsn) {
                    $builder->whereRelation('record_type', [
                        'record_types.key' => $this->isHashingBsn() ? 'bsn_hash': 'bsn',
                    ]);

                    $builder->whereIn('value', $this->isHashingBsn() ? array_filter([
                        $this->getTrustedRecordOfType($identity->address, 'partner_bsn_hash')?->value ?: null,
                        $identityBsn ? $this->getHashedValue($identityBsn) : null
                    ]) : [$identityBsn ?: null]);
                });
            });

            $builder->whereHas('validations', function(Builder $builder) {
                $builder->whereIn('identity_address', $this->validatorEmployees());
            });
        })->exists();
    }

    /**
     * @return bool
     */
    public function isAutoValidatingRequests(): bool
    {
        return $this->default_validator_employee_id && $this->auto_requests_validation;
    }

    /**
     * @return float
     */
    public function getMaxAmountPerVoucher(): float
    {
        return min(
            $this->budget_left,
            $this->fund_config->limit_generator_amount,
            $this->fund_config->limit_voucher_total_amount,
        );
    }

    /**
     * @return float
     */
    public function getMaxAmountSumVouchers(): float
    {
        return (float) ($this->fund_config->limit_generator_amount ? $this->budget_left : 1000000);
    }

    /**
     * @param Identity $identity
     * @return bool
     */
    public function identityHasActiveVoucher(Identity $identity): bool
    {
        return VoucherQuery::whereNotExpired($this->vouchers()->getQuery())->where([
            'identity_address' => $identity->address,
        ])->exists();
    }

    /**
     * @param Identity $identity
     * @return ResidencyResponse|PartnerBsnResponse|EligibilityResponse|null
     */
    public function checkBackofficeIfAvailable(
        Identity $identity
    ): EligibilityResponse|ResidencyResponse|PartnerBsnResponse|null {
        $bsn = $identity?->bsn;
        $alreadyHasActiveVoucher = $this->identityHasActiveVoucher($identity);

        if ($bsn && !$alreadyHasActiveVoucher && $this->isBackofficeApiAvailable()) {
            // check for residency
            $backofficeApi = $this->getBackofficeApi();
            $residencyResponse = $backofficeApi->residencyCheck($bsn);

            if (!$residencyResponse->getLog()->success() || !$residencyResponse->isResident()) {
                return $residencyResponse;
            }

            // check if taken by partner
            if ($this->fund_config->backoffice_check_partner) {
                $partnerBsnResponse = $backofficeApi->partnerBsn($bsn);
                $partner = Identity::findByBsn($partnerBsnResponse->getBsn() ?: null);

                if (!$partnerBsnResponse->getLog()->success() ||
                    ($partner && $this->identityHasActiveVoucher($partner)) ||
                    $this->isTakenByPartner($identity)) {
                    return $partnerBsnResponse;
                }
            }

            // check again for active vouchers
            $response = $backofficeApi->eligibilityCheck($bsn, $residencyResponse->getLog()->response_id);

            if ($response->isEligible() && !$this->identityHasActiveVoucher($identity)) {
                $extraFields = ['fund_backoffice_log_id' => $response->getLog()->id];
                $voucher = $this->makeVoucher($identity->address, $extraFields);
                $this->makeFundFormulaProductVouchers($identity->address, $extraFields);

                $response->getLog()->update([
                    'voucher_id' => $voucher->id,
                ]);
            }

            return $response;
        }

        return null;
    }

    /**
     * @param ResidencyResponse|PartnerBsnResponse|EligibilityResponse|null $response
     * @return array|null
     */
    public function backofficeResponseToData(
        ResidencyResponse|PartnerBsnResponse|EligibilityResponse|null $response
    ): ?array {
        // backoffice not available
        if ($response === null) {
            return null;
        }

        // backoffice not responding
        if (!$response->getLog()->success()) {
            return $this->backofficeError('no_response', $this->fund_config->backoffice_fallback);
        }

        // not resident
        if ($response instanceof ResidencyResponse && !$response->isResident()) {
            return $this->backofficeError('not_resident');
        }

        // is partner bsn
        if ($response instanceof PartnerBsnResponse) {
            return $this->backofficeError('taken_by_partner');
        }

        // not eligible
        if ($response instanceof EligibilityResponse && !$response->isEligible()) {
            if ($this->fund_config->shouldRedirectOnIneligibility()) {
                return [
                    'backoffice_redirect' => $this->fund_config->backoffice_ineligible_redirect_url,
                ];
            }

            // should show error
            return $this->backofficeError('not_eligible', true);
        }

        return null;
    }

    /**
     * @param string $error_key
     * @param bool $fallback
     * @return array
     */
    protected function backofficeError(string $error_key, bool $fallback = false): array
    {
        return [
            'backoffice_error' => 1,
            'backoffice_error_key' => $error_key,
            'backoffice_fallback' => $fallback ? 1 : 0,
        ];
    }

    /**
     * @param bool $skipEnabledCheck
     * @return ?BackofficeApi
     */
    public function getBackofficeApi(bool $skipEnabledCheck = false): ?BackofficeApi
    {
        if ($this->isBackofficeApiAvailable($skipEnabledCheck)) {
            return new BackofficeApi($this);
        }

        return null;
    }

    /**
     * @param bool $skipEnabledCheck
     * @return bool
     */
    public function isBackofficeApiAvailable(bool $skipEnabledCheck = false): bool
    {
        return
            $this->organization->bsn_enabled &&
            $this->organization->backoffice_available &&
            ($this->fund_config->backoffice_enabled || $skipEnabledCheck);
    }

    /**
     * @param string $default
     * @return string|null
     * @noinspection PhpUnused
     */
    public function communicationType(string $default = 'formal'): string
    {
        if ($this->fund_config && $this->fund_config->implementation) {
            return $this->fund_config->implementation->communicationType();
        }

        return $default;
    }

    /**
     * @return bool
     */
    public function hasIConnectApiOin(): bool
    {
        return
            $this->fund_config &&
            !$this->is_external &&
            $this->isIconnectApiConfigured() &&
            $this->organization->bsn_enabled &&
            !empty($this->fund_config->iconnect_target_binding) &&
            !empty($this->fund_config->iconnect_api_oin) &&
            !empty($this->fund_config->iconnect_base_url);
    }

    /**
     * @return bool
     */
    private function isIconnectApiConfigured(): bool
    {
        return
            $this->fund_config &&
            !empty($this->fund_config->iconnect_env) &&
            !empty($this->fund_config->iconnect_key) &&
            !empty($this->fund_config->iconnect_cert) &&
            !empty($this->fund_config->iconnect_cert_trust);
    }

    /**
     * @return IConnect|null
     */
    public function getIConnect(): ?IConnect
    {
        return $this->hasIConnectApiOin() ? new IConnect($this) : null;
    }

    /**
     * @param Identity $identity
     * @return bool
     */
    public function identityRequireBsnConfirmation(Identity $identity): bool
    {
        $record = $identity->activeBsnRecord();
        $recordTime = $record?->created_at?->diffInSeconds(now());

        if ($this->fund_config && $this->fund_config->bsn_confirmation_api_time === null) {
            return false;
        }

        return empty($record) || $recordTime > $this->fund_config?->bsn_confirmation_api_time;
    }

    /**
     * @return bool
     */
    public function generatorDirectPaymentsAllowed(): bool
    {
        return
            $this->isTypeBudget() &&
            $this->fund_config?->allow_direct_payments &&
            $this->fund_config?->allow_generator_direct_payments;
    }
}
