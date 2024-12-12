<?php

namespace App\Models;

use App\Events\Funds\FundArchivedEvent;
use App\Events\Funds\FundUnArchivedEvent;
use App\Events\Vouchers\VoucherCreated;
use App\Mail\Forus\FundStatisticsMail;
use App\Models\Data\BankAccount;
use App\Models\Traits\HasFaq;
use App\Models\Traits\HasTags;
use App\Rules\FundRequests\BaseFundRequestRule;
use App\Scopes\Builders\FundProviderQuery;
use App\Scopes\Builders\RecordValidationQuery;
use App\Scopes\Builders\VoucherQuery;
use App\Services\BackofficeApiService\BackofficeApi;
use App\Services\BackofficeApiService\Responses\EligibilityResponse;
use App\Services\BackofficeApiService\Responses\PartnerBsnResponse;
use App\Services\BackofficeApiService\Responses\ResidencyResponse;
use App\Services\BankService\Models\Bank;
use App\Services\EventLogService\Traits\HasDigests;
use App\Services\EventLogService\Traits\HasLogs;
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
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * App\Models\Fund
 *
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string|null $description
 * @property string|null $description_text
 * @property string|null $description_short
 * @property string $description_position
 * @property int|null $parent_id
 * @property string|null $faq_title
 * @property string $request_btn_text
 * @property string|null $external_link_url
 * @property string $external_link_text
 * @property bool $external_page
 * @property string|null $external_page_url
 * @property string|null $type
 * @property string $state
 * @property bool $archived
 * @property bool $public
 * @property bool $criteria_editable_after_start
 * @property string|null $notification_amount
 * @property \Illuminate\Support\Carbon|null $notified_at
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $default_validator_employee_id
 * @property bool $auto_requests_validation
 * @property-read Collection|\App\Models\FundAmountPreset[] $amount_presets
 * @property-read int|null $amount_presets_count
 * @property-read Collection|\App\Models\FundBackofficeLog[] $backoffice_logs
 * @property-read int|null $backoffice_logs_count
 * @property-read Collection|\App\Models\Voucher[] $budget_vouchers
 * @property-read int|null $budget_vouchers_count
 * @property-read Collection|Fund[] $children
 * @property-read int|null $children_count
 * @property-read Collection|\App\Models\FundCriterion[] $criteria
 * @property-read int|null $criteria_count
 * @property-read Collection|\App\Models\FundCriteriaStep[] $criteria_steps
 * @property-read int|null $criteria_steps_count
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
 * @property-read Collection|\App\Models\FundPeriod[] $fund_periods
 * @property-read int|null $fund_periods_count
 * @property-read Collection|\App\Models\FundProvider[] $fund_providers
 * @property-read int|null $fund_providers_count
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
 * @property-read Fund|null $parent
 * @property-read Collection|\App\Models\Voucher[] $product_vouchers
 * @property-read int|null $product_vouchers_count
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
 * @method static Builder<static>|Fund newModelQuery()
 * @method static Builder<static>|Fund newQuery()
 * @method static Builder<static>|Fund query()
 * @method static Builder<static>|Fund whereArchived($value)
 * @method static Builder<static>|Fund whereAutoRequestsValidation($value)
 * @method static Builder<static>|Fund whereCreatedAt($value)
 * @method static Builder<static>|Fund whereCriteriaEditableAfterStart($value)
 * @method static Builder<static>|Fund whereDefaultValidatorEmployeeId($value)
 * @method static Builder<static>|Fund whereDescription($value)
 * @method static Builder<static>|Fund whereDescriptionPosition($value)
 * @method static Builder<static>|Fund whereDescriptionShort($value)
 * @method static Builder<static>|Fund whereDescriptionText($value)
 * @method static Builder<static>|Fund whereEndDate($value)
 * @method static Builder<static>|Fund whereExternalLinkText($value)
 * @method static Builder<static>|Fund whereExternalLinkUrl($value)
 * @method static Builder<static>|Fund whereExternalPage($value)
 * @method static Builder<static>|Fund whereExternalPageUrl($value)
 * @method static Builder<static>|Fund whereFaqTitle($value)
 * @method static Builder<static>|Fund whereId($value)
 * @method static Builder<static>|Fund whereName($value)
 * @method static Builder<static>|Fund whereNotificationAmount($value)
 * @method static Builder<static>|Fund whereNotifiedAt($value)
 * @method static Builder<static>|Fund whereOrganizationId($value)
 * @method static Builder<static>|Fund whereParentId($value)
 * @method static Builder<static>|Fund wherePublic($value)
 * @method static Builder<static>|Fund whereRequestBtnText($value)
 * @method static Builder<static>|Fund whereStartDate($value)
 * @method static Builder<static>|Fund whereState($value)
 * @method static Builder<static>|Fund whereType($value)
 * @method static Builder<static>|Fund whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Fund extends BaseModel
{
    use HasMedia, HasTags, HasLogs, HasDigests, HasMarkdownDescription, HasFaq;

    public const string EVENT_CREATED = 'created';
    public const string EVENT_UPDATED = 'updated';
    public const string EVENT_PROVIDER_APPLIED = 'provider_applied';
    public const string EVENT_PROVIDER_REPLIED = 'provider_replied';
    public const string EVENT_PROVIDER_APPROVED_PRODUCTS = 'provider_approved_products';
    public const string EVENT_PROVIDER_APPROVED_BUDGET = 'provider_approved_budget';
    public const string EVENT_PROVIDER_REVOKED_PRODUCTS = 'provider_revoked_products';
    public const string EVENT_PROVIDER_REVOKED_BUDGET = 'provider_revoked_budget';
    public const string EVENT_BALANCE_LOW = 'balance_low';
    public const string EVENT_BALANCE_SUPPLIED = 'balance_supplied';
    public const string EVENT_FUND_STARTED = 'fund_started';
    public const string EVENT_FUND_ENDED = 'fund_ended';
    public const string EVENT_PRODUCT_ADDED = 'fund_product_added';
    public const string EVENT_PRODUCT_APPROVED = 'fund_product_approved';
    public const string EVENT_PRODUCT_REVOKED = 'fund_product_revoked';
    public const string EVENT_PRODUCT_SUBSIDY_REMOVED = 'fund_product_subsidy_removed';
    public const string EVENT_FUND_EXPIRING = 'fund_expiring';
    public const string EVENT_ARCHIVED = 'archived';
    public const string EVENT_UNARCHIVED = 'unarchived';
    public const string EVENT_VOUCHERS_EXPORTED = 'vouchers_exported';
    public const string EVENT_SPONSOR_NOTIFICATION_CREATED = 'sponsor_notification_created';
    public const string EVENT_PERIOD_EXTENDED = 'period_extended';

    public const string STATE_ACTIVE = 'active';
    public const string STATE_CLOSED = 'closed';
    public const string STATE_PAUSED = 'paused';
    public const string STATE_WAITING = 'waiting';

    public const array STATES = [
        self::STATE_ACTIVE,
        self::STATE_CLOSED,
        self::STATE_PAUSED,
        self::STATE_WAITING,
    ];

    public const array STATES_PUBLIC = [
        self::STATE_ACTIVE,
        self::STATE_PAUSED,
        self::STATE_CLOSED,
    ];

    public const string TYPE_BUDGET = 'budget';
    public const string TYPE_EXTERNAL = 'external';
    public const string TYPE_SUBSIDIES = 'subsidies';

    public const array TYPES = [
        self::TYPE_BUDGET,
        self::TYPE_SUBSIDIES,
        self::TYPE_EXTERNAL,
    ];

    const string DESCRIPTION_POSITION_AFTER = 'after';
    const string DESCRIPTION_POSITION_BEFORE = 'before';
    const string DESCRIPTION_POSITION_REPLACE = 'replace';

    const array DESCRIPTION_POSITIONS = [
        self::DESCRIPTION_POSITION_AFTER,
        self::DESCRIPTION_POSITION_BEFORE,
        self::DESCRIPTION_POSITION_REPLACE,
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
        'description_position', 'external_page', 'external_page_url', 'pre_check_note',
    ];

    protected $hidden = [
        'fund_config', 'fund_formulas'
    ];

    protected $casts = [
        'public' => 'boolean',
        'archived' => 'boolean',
        'end_date' => 'datetime',
        'start_date' => 'datetime',
        'notified_at' => 'datetime',
        'external_page' => 'boolean',
        'auto_requests_validation' => 'boolean',
        'criteria_editable_after_start' => 'boolean',
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
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function criteria_steps(): HasMany
    {
        return $this->hasMany(FundCriteriaStep::class);
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function fund_periods(): HasMany
    {
        return $this->hasMany(FundPeriod::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function product_vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class)->whereNotNull('product_id');
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
        return $this->morphToMany(Tag::class, 'taggable')->where('scope', 'webshop');
    }

    /**
     * @return MorphToMany
     * @noinspection PhpUnused
     */
    public function tags_provider(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')->where('scope', 'provider');
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
            'outcome_type' => Arr::get($attributes, 'outcome_type', FundConfig::OUTCOME_TYPE_VOUCHER),
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
        $values = Arr::only($attributes, [
            'allow_fund_requests', 'allow_prevalidations', 'allow_direct_requests',
            'email_required', 'contact_info_enabled', 'contact_info_required',
            'contact_info_message_custom', 'contact_info_message_text',
            'auth_2fa_policy', 'auth_2fa_remember_ip',
            'auth_2fa_restrict_emails', 'auth_2fa_restrict_auth_sessions',
            'auth_2fa_restrict_reimbursements', 'hide_meta', 'voucher_amount_visible',
            'allow_custom_amounts', 'allow_custom_amounts_validator',
            'allow_preset_amounts', 'allow_preset_amounts_validator',
            'custom_amount_min', 'custom_amount_max', 'provider_products_required',
            'help_enabled', 'help_title', 'help_block_text', 'help_button_text',
            'help_email', 'help_phone', 'help_website', 'help_chat', 'help_description',
            'help_show_email', 'help_show_phone', 'help_show_website', 'help_show_chat',
            'custom_amount_min', 'custom_amount_max', 'criteria_label_requirement_show',
            'pre_check_excluded', 'pre_check_note',
        ]);

        $replaceValues = $this->isExternal() ? array_fill_keys([
            'allow_fund_requests', 'allow_prevalidations', 'allow_direct_requests',
        ], false) : [];

        $this->fund_config->forceFill(array_merge($values, $replaceValues))->save();
    }

    /**
     * @param array $presets
     * @return void
     */
    public function syncAmountPresets(array $presets): void
    {
        $this->amount_presets()
            ->whereNotIn('id', array_filter(Arr::pluck($presets, 'id')))
            ->delete();

        foreach ($presets as $preset) {
            $data = array_only($preset, ['name', 'amount']);

            $preset['id'] ?? null ?
                $this->amount_presets()->find($preset['id'])->update($data) :
                $this->amount_presets()->create($data);
        }
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function fund_providers(): HasMany
    {
        return $this->hasMany(FundProvider::class);
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
        return round($this->top_up_transactions->sum('amount'), 2);
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
        return round($this->budget_total - $this->budget_used, 2);
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
     * @param int|null $year
     * @return float
     */
    public function getTransactionCosts(int $year = null): float
    {
        $costs = 0;
        $state = VoucherTransaction::STATE_SUCCESS;
        $targets = VoucherTransaction::TARGETS_OUTGOING;
        $targetCostOld = VoucherTransaction::TRANSACTION_COST_OLD;

        foreach (Bank::get() as $bank) {
            $costs += $this->voucher_transactions()
                ->where('voucher_transactions.amount', '>', 0)
                ->where('voucher_transactions.state', $state)
                ->whereIn('voucher_transactions.target', $targets)
                ->whereRelation('voucher_transaction_bulk.bank_connection', 'bank_id', $bank->id)
                ->whereYear('voucher_transactions.created_at', $year ?: now()->year)
                ->count() * $bank->transaction_cost;
        }

        $costs += $this->voucher_transactions()
            ->where('voucher_transactions.amount', '>', 0)
                ->where('voucher_transactions.state', $state)
                ->whereIn('voucher_transactions.target', $targets)
                ->whereDoesntHave('voucher_transaction_bulk')
                ->whereYear('voucher_transactions.created_at', $year ?: now()->year)
                ->count() * $targetCostOld;

        return $costs;
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
            $builder->where('key', Permission::VALIDATE_RECORDS);
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
            $builder->where('key', Permission::MANAGE_VALIDATORS);
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
    public function amount_presets(): HasMany
    {
        return $this->hasMany(FundAmountPreset::class);
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
     * @return BelongsToMany
     * @noinspection PhpUnused
     */
    public function formula_products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'fund_formula_products');
    }

    /**
     * @param string $identity_address
     * @param array $record_types
     * @return array|Record[]
     */
    public function getTrustedRecordOfTypes(
        string $identity_address,
        array $record_types,
    ): array {
        return array_combine($record_types, array_map(fn ($record_type) => $this->getTrustedRecordOfType(
            $identity_address,
            $record_type,
        )?->value, $record_types));
    }

    /**
     * @param string $identity_address
     * @param string $record_type
     * @return Model|Record|null
     */
    public function getTrustedRecordOfType(
        string $identity_address,
        string $record_type,
    ): Record|Model|null {
        $fund = $this;
        $daysTrusted = $this->getTrustedDays($record_type);
        $startDate = $this->fund_config?->record_validity_start_date;

        $builder = Record::search(Identity::findByAddress($identity_address)->records(), [
            'type' => $record_type,
        ])->whereHas('validations', function(Builder $query) use ($daysTrusted, $fund, $startDate) {
            RecordValidationQuery::whereStillTrustedQuery($query, $daysTrusted, $startDate);
            RecordValidationQuery::whereTrustedByQuery($query, $fund);
        });

        $validationSubQuery = RecordValidation::where(function(Builder $query) use ($daysTrusted, $fund, $startDate) {
            $query->whereColumn('records.id', '=', 'record_validations.record_id');
            RecordValidationQuery::whereStillTrustedQuery($query, $daysTrusted, $startDate);
            RecordValidationQuery::whereTrustedByQuery($query, $fund);
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
     * @param array|null $records
     * @return float
     */
    public function amountForIdentity(?string $identityAddress, array $records = null): float
    {
        if ($this->fund_formulas->count() === 0 &&
            $this->fund_formula_products->pluck('price')->sum() === 0) {
            return 0;
        }

        return $this->fund_formulas->map(function(FundFormula $formula) use ($identityAddress, $records) {
            switch ($formula->type) {
                case 'fixed': return $formula->amount;
                case 'multiply': {
                    if ($records) {
                        $value = $records[$formula->record_type_key] ?? null;
                    } else {
                        $record = $this->getTrustedRecordOfType(
                            $identityAddress,
                            $formula->record_type_key,
                        );

                        $value = $record?->value;
                    }

                    return is_numeric($value) ? $formula->amount * $value : 0;
                }
                default: return 0;
            }
        })->sum() + $this->fund_formula_products->pluck('price')->sum();
    }

    /**
     * @param string|null $identityAddress
     * @param array|null $records
     * @return int
     */
    public function multiplierForIdentity(?string $identityAddress, array $records = null): int {
        /** @var FundLimitMultiplier[]|Collection $multipliers */
        $multipliers = $this->fund_limit_multipliers()->get();

        if ((!$identityAddress && !$records) || ($multipliers->count() === 0)) {
            return 1;
        }

        return $multipliers->map(function(FundLimitMultiplier $multiplier) use ($identityAddress, $records) {
            if ($records) {
                $value = (int) ($records[$multiplier->record_type_key] ?: 1);
            } else {
                $record = $this->getTrustedRecordOfType(
                    $identityAddress,
                    $multiplier->record_type_key,
                );

                $value = (int) ($record ? $record->value: 1);
            }

            return $value * $multiplier->multiplier;
        })->sum();
    }

    /**
     * @return mixed|null
     */
    public function amountFixedByFormula(): mixed
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
     * @param bool $withOptional
     * @param array $values
     * @return array
     */
    public function requiredPrevalidationKeys(bool $withOptional, array $values): array
    {
        $criteriaKeys = $withOptional ?
            $this->criteria
                ?->pluck('record_type_key')
                ?->toArray() ?? [] :
            $this->criteria
                ?->filter(function (FundCriterion $criterion) use ($values) {
                    return !$criterion->optional && !$criterion->isExcludedByRules($values);
                })
                ?->pluck('record_type_key')
                ?->toArray() ?? [];

        $formulaKeys = $this->fund_formulas
            ?->where('type', 'multiply')
            ?->pluck('record_type_key')
            ?->toArray() ?? [];

        $list = array_filter([
            $this?->fund_config?->csv_primary_key,
            ...$criteriaKeys,
            ...$formulaKeys,
        ]);

        return array_values(array_unique($list));
    }

    /**
     * Change fund state
     *
     * @param string $state
     * @return $this
     */
    public function changeState(string $state): self
    {
        if (in_array($state, self::STATES)) {
            $this->update(compact('state'));
        }

        return $this;
    }

    /**
     * Send funds user count statistic to email
     * @param string $email
     * @return void
     */
    public static function sendUserStatisticsReport(string $email): void
    {
        $funds = Fund::whereHas('fund_config', static function (Builder $query) {
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
     * @param array $voucherFields
     * @param string|FundAmountPreset|null $amount
     * @param Carbon|null $expire_at
     * @param int|null $limit_multiplier
     * @return Voucher|null
     */
    public function makeVoucher(
        string $identity_address = null,
        array $voucherFields = [],
        string|FundAmountPreset $amount = null,
        Carbon $expire_at = null,
        ?int $limit_multiplier = null,
    ): ?Voucher {
        $presetModel = $amount instanceof FundAmountPreset ? $amount : null;

        if ($this->fund_formulas->count() === 0 && $amount === null) {
            return null;
        }

        $amount = $presetModel ? $presetModel->amount : $amount;
        $amount = $amount === null ? $this->amountForIdentity($identity_address) : $amount;

        $voucher = Voucher::create([
            'number' => Voucher::makeUniqueNumber(),
            'identity_address' => $identity_address,
            'amount' => $amount,
            'expire_at' => $expire_at ?: $this->end_date,
            'fund_id' => $this->id,
            'returnable' => false,
            'limit_multiplier' => $limit_multiplier ?: $this->multiplierForIdentity($identity_address),
            'fund_amount_preset_id' => $presetModel?->id,
            ...$voucherFields,
        ]);

        VoucherCreated::dispatch($voucher);

        return $voucher;
    }

    /**
     * @param FundAmountPreset|string|null $amount
     * @param Employee $employee
     * @param BankAccount $bankAccount
     * @param array $voucherFields
     * @param array $transactionFields
     * @return VoucherTransaction
     */
    public function makePayout(
        FundAmountPreset|string|null $amount,
        Employee $employee,
        BankAccount $bankAccount,
        array $voucherFields = [],
        array $transactionFields = [],
    ): VoucherTransaction {
        $voucher = $this->makeVoucher(null, [
            'voucher_type' => Voucher::VOUCHER_TYPE_PAYOUT,
            'employee_id' => $employee->id,
            ...$voucherFields,
        ], $amount);

        return $voucher->makeTransactionBySponsor($employee, [
            'amount' => $voucher->amount,
            'target' => VoucherTransaction::TARGET_PAYOUT,
            'target_iban' => $bankAccount->getIban(),
            'target_name' => $bankAccount->getName(),
            'employee_id' => $employee->id,
            'transfer_at' => now()->addDay(),
            ...$transactionFields,
        ]);
    }

    /**
     * @param string|null $identityAddress
     * @param array $voucherFields
     * @param Carbon|null $expireAt
     * @return Voucher[]
     */
    public function makeFundFormulaProductVouchers(
        string $identityAddress = null,
        array $voucherFields = [],
        Carbon $expireAt = null
    ): array {
        $vouchers = [];
        $fundEndDate = $this->end_date;

        foreach ($this->fund_formula_products as $formulaProduct) {
            $productExpireDate = $formulaProduct->product->expire_at;
            $voucherExpireAt = $productExpireDate && $fundEndDate->gt($productExpireDate) ? $productExpireDate : $fundEndDate;
            $voucherExpireAt = $expireAt && $voucherExpireAt->gt($expireAt) ? $expireAt : $voucherExpireAt;
            $multiplier = $formulaProduct->getIdentityMultiplier($identityAddress);

            $vouchers = array_merge($vouchers, array_map(fn () => $this->makeProductVoucher(
                $identityAddress,
                $voucherFields,
                $formulaProduct->product->id,
                $voucherExpireAt,
                $formulaProduct->price
            ), array_fill(0, $multiplier, null)));
        }

        return $vouchers;
    }

    /**
     * @param string|null $identity_address
     * @param array $voucherFields
     * @param int|null $product_id
     * @param Carbon|null $expire_at
     * @param float|null $price
     * @return Voucher
     */
    public function makeProductVoucher(
        string $identity_address = null,
        array $voucherFields = [],
        int $product_id = null,
        Carbon $expire_at = null,
        float $price = null,
    ): Voucher {
        $voucher = Voucher::create([
            'number' => Voucher::makeUniqueNumber(),
            'amount' => $price ?: Product::findOrFail($product_id)->price,
            'fund_id' => $this->id,
            'expire_at' => $expire_at ?: $this->end_date,
            'product_id' => $product_id,
            'returnable' => false,
            'identity_address' => $identity_address,
            ...$voucherFields,
        ]);

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
        return $this->end_date->clone()->endOfDay()->isPast();
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
     * @return array
     */
    public function validatorEmployees(): array
    {
        return $this->employees_validators()->pluck('employees.identity_address')->toArray();
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
            /** @var FundCriterion $criteria */
            $criteria = $this->criteria()->find($record['fund_criterion_id'] ?? null);
            $value = Arr::get($record, 'value');

            if ($criteria->optional && ($value === '' || $value === null)) {
                continue;
            }

            /** @var FundRequestRecord $requestRecord */
            $requestRecord = $fundRequest->records()->create(array_merge($record, [
                'record_type_key' => $criteria->record_type_key,
            ]));

            $requestRecord->appendFilesByUid($record['files'] ?? []);
        }

        return $fundRequest;
    }

    /**
     * Update criteria for existing fund
     * @param array $criteria
     * @param bool $textsOnly
     * @return $this
     */
    public function syncCriteria(array $criteria, bool $textsOnly = false): self
    {
        // remove criteria not listed in the array
        if ($this->criteriaIsEditable() && !$textsOnly) {
            $this->criteria()->whereNotIn('id', array_filter(
                array_pluck($criteria, 'id'), fn ($id) => !empty($id)
            ))->delete();
        }

        foreach ($criteria as $criterion) {
            $this->syncCriterion($criterion, $textsOnly);
        }

        return $this;
    }

    /**
     * Update existing or create new fund criterion
     * @param array $criterion
     * @param bool $textsOnly
     */
    protected function syncCriterion(array $criterion, bool $textsOnly = false): void
    {
        $fundCriterion = $this->criteria()->find($criterion['id'] ?? null);

        if (!$fundCriterion && !$this->criteriaIsEditable()) {
            return;
        }

        /** @var FundCriterion|null $db_criteria */
        $data_criterion = array_only($criterion, $this->criteriaIsEditable() ? [
            'record_type_key', 'operator', 'value', 'show_attachment',
            'description', 'title', 'optional', 'min', 'max', 'label',
            'extra_description',
        ] : ['show_attachment', 'description', 'title', 'extra_description']);

        if ($this->criteriaIsEditable()) {
            $data_criterion['value'] = Arr::get($data_criterion, 'value', '') ?: '';
            $data_criterion['operator'] = Arr::get($data_criterion, 'operator', '') ?: '';
        }

        if ($fundCriterion) {
            $fundCriterion->update($textsOnly ? array_only($data_criterion, [
                'title', 'description', 'extra_description',
            ]): $data_criterion);
        } elseif (!$textsOnly) {
            $this->criteria()->create($data_criterion);
        }
    }

    /**
     * @param Identity $identity
     * @param FundCriterion $criterion
     * @return bool
     */
    public function checkFundCriteria(
        Identity $identity,
        FundCriterion $criterion,
    ): bool {
        $record_type = $criterion->record_type;
        $value = $this->getTrustedRecordOfType($identity->address, $record_type->key)?->value;

        $records = $criterion->fund_criterion_rules->pluck('record_type_key')->unique()->toArray();
        $recordsValues = $this->getTrustedRecordOfTypes($identity->address, $records);

        return
            $criterion->isExcludedByRules($recordsValues) ||
            BaseFundRequestRule::validateRecordValue($criterion, $value)->passes();
    }

    /**
     * @param array $items
     * @return $this
     */
    public function updateFormulaProducts(array $items): self
    {
        $products = array_map(fn (array $item) => $this->fund_formula_products()->updateOrCreate([
            'product_id' => Arr::get($item, 'product_id'),
            'record_type_key_multiplier' => Arr::get($item, 'record_type_key_multiplier'),
        ])->id, $items);

        $this->fund_formula_products()->whereNotIn('id', $products)->delete();

        return $this;
    }

    /**
     * @param string $uri
     * @return string|null
     */
    public function urlWebshop(string $uri = "/"): string|null
    {
        return $this->fund_config?->implementation?->urlWebshop($uri);
    }

    /**
     * @param string $uri
     * @return string|null
     */
    public function urlSponsorDashboard(string $uri = "/"): string|null
    {
        return $this->fund_config?->implementation?->urlSponsorDashboard($uri);
    }

    /**
     * @param string $uri
     * @return string|null
     */
    public function urlProviderDashboard(string $uri = "/"): string|null
    {
        return $this->fund_config?->implementation?->urlProviderDashboard($uri);
    }

    /**
     * @param string $uri
     * @return string|null
     */
    public function urlValidatorDashboard(string $uri = "/"): string|null
    {
        return $this->fund_config?->implementation?->urlValidatorDashboard($uri);
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
            $this->fund_config->limit_generator_amount,
            $this->fund_config->limit_voucher_total_amount,
            $this->fund_config->generator_ignore_fund_budget ? 1_000_000 : $this->budget_left,
        );
    }

    /**
     * @return float
     */
    public function getMaxAmountSumVouchers(): float
    {
        return $this->fund_config->generator_ignore_fund_budget ? 1_000_000 : $this->budget_left;
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
        $bsn = $identity->bsn;
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
                    'voucher_id' => $voucher?->id,
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
            ($this->fund_config?->backoffice_enabled || $skipEnabledCheck);
    }

    /**
     * @param string $default
     * @return string
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
        $record = $identity->record_bsn;
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

    /**
     * @return ?string
     */
    public function getResolvingError(): ?string
    {
        if ($this->fund_config->isPayoutOutcome()) {
            if (!$this->fund_config->iban_record_key || !$this->fund_config->iban_name_record_key) {
                return "invalid_iban_record_keys";
            }

            if ($this->organization->fund_request_resolve_policy ===
                Organization::FUND_REQUEST_POLICY_MANUAL) {
                return "invalid_fund_request_manual_policy";
            }
        }

        return null;
    }

    /**
     * @param bool $excluded
     * @param string $note
     * @return void
     */
    public function updatePreCheckExclusion(bool $excluded, string $note): void
    {
        $this->updateFundsConfig([
            'pre_check_note' => $note,
            'pre_check_excluded' => $excluded,
        ]);
    }

    /**
     * @return void
     */
    public function removePreCheckExclusion(): void
    {
        $this->updateFundsConfig([
            'pre_check_note' => null,
            'pre_check_excluded' => false,
        ]);
    }

    /**
     * @param string $error
     * @param array $data
     * @return void
     */
    public function logError(string $error, array $data = []): void
    {
        Log::channel('funds')->error(json_pretty([
            'error' => "[$error]",
            'fund_id' => $this->id,
            ...$data,
        ]));
    }
}
