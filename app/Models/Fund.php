<?php

namespace App\Models;

use App\Events\Funds\FundBalanceLowEvent;
use App\Events\Funds\FundEndedEvent;
use App\Events\Funds\FundExpiringEvent;
use App\Events\Funds\FundStartedEvent;
use App\Events\Vouchers\VoucherAssigned;
use App\Events\Vouchers\VoucherCreated;
use App\Scopes\Builders\VoucherQuery;
use App\Services\EventLogService\Traits\HasDigests;
use App\Services\EventLogService\Traits\HasLogs;
use App\Models\Traits\HasTags;
use App\Scopes\Builders\FundCriteriaQuery;
use App\Scopes\Builders\FundCriteriaValidatorQuery;
use App\Scopes\Builders\FundProviderQuery;
use App\Scopes\Builders\FundRequestQuery;
use App\Scopes\Builders\FundQuery;
use App\Services\BunqService\BunqService;
use App\Services\FileService\Models\File;
use App\Services\Forus\Identity\Models\Identity;
use App\Services\Forus\Notification\EmailFrom;
use App\Services\Forus\Notification\NotificationService;
use App\Services\Forus\Record\Repositories\RecordRepo;
use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Traits\HasMedia;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Http\Request;

/**
 * App\Models\Fund
 *
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string|null $description
 * @property string $type
 * @property string $state
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
 * @property-read Collection|\App\Models\Voucher[] $budget_vouchers
 * @property-read int|null $budget_vouchers_count
 * @property-read Collection|\App\Models\BunqMeTab[] $bunq_me_tabs
 * @property-read int|null $bunq_me_tabs_count
 * @property-read Collection|\App\Models\BunqMeTab[] $bunq_me_tabs_paid
 * @property-read int|null $bunq_me_tabs_paid_count
 * @property-read Collection|\App\Models\FundCriterion[] $criteria
 * @property-read int|null $criteria_count
 * @property-read \App\Models\Employee|null $default_validator_employee
 * @property-read Collection|\App\Services\EventLogService\Models\Digest[] $digests
 * @property-read int|null $digests_count
 * @property-read Collection|\App\Models\Employee[] $employees
 * @property-read int|null $employees_count
 * @property-read Collection|\App\Models\Employee[] $employees_validators
 * @property-read int|null $employees_validators_count
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
 * @property-read float $budget_used
 * @property-read float $budget_validated
 * @property-read \App\Models\FundTopUp $top_up_model
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
 * @property-read Collection|\App\Models\Organization[] $provider_organizations_approved_budget
 * @property-read int|null $provider_organizations_approved_budget_count
 * @property-read Collection|\App\Models\Organization[] $provider_organizations_approved_products
 * @property-read int|null $provider_organizations_approved_products_count
 * @property-read Collection|\App\Models\Organization[] $provider_organizations_declined
 * @property-read int|null $provider_organizations_declined_count
 * @property-read Collection|\App\Models\Organization[] $provider_organizations_pending
 * @property-read int|null $provider_organizations_pending_count
 * @property-read Collection|\App\Models\FundProvider[] $providers
 * @property-read int|null $providers_count
 * @property-read Collection|\App\Models\FundProvider[] $providers_allowed_products
 * @property-read int|null $providers_allowed_products_count
 * @property-read Collection|\App\Models\FundProvider[] $providers_approved
 * @property-read int|null $providers_approved_count
 * @property-read Collection|\App\Models\Tag[] $tags
 * @property-read int|null $tags_count
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
 * @method static Builder|Fund whereAutoRequestsValidation($value)
 * @method static Builder|Fund whereCreatedAt($value)
 * @method static Builder|Fund whereCriteriaEditableAfterStart($value)
 * @method static Builder|Fund whereDefaultValidatorEmployeeId($value)
 * @method static Builder|Fund whereDescription($value)
 * @method static Builder|Fund whereEndDate($value)
 * @method static Builder|Fund whereId($value)
 * @method static Builder|Fund whereName($value)
 * @method static Builder|Fund whereNotificationAmount($value)
 * @method static Builder|Fund whereNotifiedAt($value)
 * @method static Builder|Fund whereOrganizationId($value)
 * @method static Builder|Fund wherePublic($value)
 * @method static Builder|Fund whereStartDate($value)
 * @method static Builder|Fund whereState($value)
 * @method static Builder|Fund whereType($value)
 * @method static Builder|Fund whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Fund extends Model
{
    use HasMedia, HasTags, HasLogs, HasDigests;

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

    public const STATE_ACTIVE = 'active';
    public const STATE_CLOSED = 'closed';
    public const STATE_PAUSED = 'paused';
    public const STATE_WAITING = 'waiting';

    public const STATES = [
        self::STATE_ACTIVE,
        self::STATE_CLOSED,
        self::STATE_PAUSED,
        self::STATE_WAITING,
    ];

    public const TYPE_BUDGET = 'budget';
    public const TYPE_SUBSIDIES = 'subsidies';

    public const TYPES = [
        self::TYPE_BUDGET,
        self::TYPE_SUBSIDIES,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organization_id', 'state', 'name', 'description', 'start_date',
        'end_date', 'notification_amount', 'fund_id', 'notified_at', 'public',
        'default_validator_employee_id', 'auto_requests_validation',
        'criteria_editable_after_start', 'type',
    ];

    protected $hidden = [
        'fund_config', 'fund_formulas'
    ];

    protected $casts = [
        'public' => 'boolean',
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
        return $this->belongsToMany(
            Product::class,
            'fund_products'
        );
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
        return $this->hasMany(Voucher::class)->whereNull('parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function voucher_transactions(): HasManyThrough
    {
        return $this->hasManyThrough(VoucherTransaction::class, Voucher::class);
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
    public function providers_approved(): HasMany
    {
        return $this->hasMany(FundProvider::class)->where(static function(Builder $builder) {
            $builder->where('allow_budget', true);
            $builder->orWhere('allow_products', true);
            $builder->orWhere('allow_some_products', true);
        });
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
     * @param string|null $email
     * @return bool
     */
    public function sendFundClosedRequesterEmailNotification(?string $email): bool
    {
        return $email ? notification_service()->fundClosed(
            $email,
            $this->fund_config->implementation->getEmailFrom(),
            $this->name,
            $this->organization->email,
            $this->organization->name,
            $this->fund_config->implementation->url_webshop
        ) : false;
    }

    /**
     * @param Organization $organization
     * @return bool
     */
    public function sendFundClosedProviderEmailNotification(Organization $organization): bool
    {
        return notification_service()->fundClosedProvider(
            $organization->email,
            $this->fund_config->implementation->getEmailFrom(),
            $this->name,
            format_date_locale($this->start_date),
            format_date_locale($this->end_date),
            $organization->name,
            $this->fund_config->implementation->url_provider ?? env('PANEL_PROVIDER_URL')
        );
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
     * @return float
     * @noinspection PhpUnused
     */
    public function getBudgetValidatedAttribute(): float
    {
        return 0;
    }

    /**
     * @return float
     * @noinspection PhpUnused
     */
    public function getBudgetTotalAttribute(): float
    {
        return round(array_sum([
            $this->top_up_transactions->sum('amount'),
            $this->bunq_me_tabs_paid->sum('amount')
        ]), 2);
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
     * @return float
     */
    public function getServiceCosts(): float
    {
        return $this->getTransactionCosts();
    }

    /**
     * @return float
     */
    public function getTransactionCosts (): float
    {
        if ($this->fund_config && !$this->fund_config->subtract_transaction_costs) {
            return $this->voucher_transactions()->where('voucher_transactions.amount', '>', '0')->count() * 0.10;
        }

        return 0.0;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     * @noinspection PhpUnused
     */
    public function provider_organizations_approved(): BelongsToMany
    {
        return $this->belongsToMany(
            Organization::class,
            'fund_providers'
        )->where(static function(Builder $builder) {
            $builder->where('allow_budget', true);
            $builder->orWhere('allow_products', true);
            $builder->orWhere('allow_some_products', true);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     * @noinspection PhpUnused
     */
    public function provider_organizations_approved_budget(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class,
            'fund_providers'
        )->where(static function(Builder $builder) {
            $builder->where('allow_budget', true);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     * @noinspection PhpUnused
     */
    public function provider_organizations_approved_products(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'fund_providers')->where(static function(Builder $builder) {
            $builder->where('allow_products', true);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     * @noinspection PhpUnused
     */
    public function provider_organizations_declined(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'fund_providers')->where([
            'allow_budget' => false,
            'allow_products' => false,
            'dismissed' => true,
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     * @noinspection PhpUnused
     */
    public function provider_organizations_pending(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'fund_providers')->where([
            'allow_budget' => false,
            'allow_products' => false,
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     * @noinspection PhpUnused
     */
    public function provider_organizations(): BelongsToMany
    {
        return $this->belongsToMany(
            Organization::class,
            'fund_providers'
        );
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function bunq_me_tabs(): HasMany
    {
        return $this->hasMany(BunqMeTab::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function bunq_me_tabs_paid(): HasMany
    {
        return $this->hasMany(BunqMeTab::class)->where([
            'status' => 'PAID'
        ]);
    }

    /**
     * @return array|null
     */
    public function getBunqKey(): ?array
    {
        if (!$this->fund_config) {
            return null;
        }

        return [
            "key" => $this->fund_config->bunq_key,
            "sandbox" => $this->fund_config->bunq_sandbox,
            "allowed_ip" => $this->fund_config->bunq_allowed_ip,
        ];
    }

    /**
     * @param string $identity_address
     * @param string $record_type
     * @param FundCriterion|null $criterion
     * @return mixed
     */
    public function getTrustedRecordOfType(
        string $identity_address,
        string $record_type,
        FundCriterion $criterion = null
    ) {
        $fund = $this;
        $organization = $fund->organization;
        $recordRepo = resolve('forus.services.record');
        $trustedIdentities = $fund->validatorEmployees($criterion);
        $daysTrusted = $this->getTrustedDays($record_type);
        $recordsOfType = $recordRepo->recordsList($identity_address, $record_type, null,false, $daysTrusted);

        $validRecordsOfType = collect($recordsOfType)->map(static function($record) use (
            $trustedIdentities, $organization, $criterion, $record_type, $daysTrusted
        ) {
            $validations = collect($record['validations']);
            $validations = $validations->whereIn('identity_address', $trustedIdentities);
            $validations = $validations->filter(static function($validation) use ($organization) {
                return is_null($validation['organization_id']) ||
                    ($validation['organization_id'] === $organization->id);
            });

            return array_merge($record, [
                'validations' => $validations->sortByDesc('created_at')->values()->toArray()
            ]);
        })->filter(static function($record) {
            return count($record['validations']) > 0;
        })->sortByDesc(static function($record) {
            return $record['validations'][0]['validation_date_timestamp'];
        });

        return $validRecordsOfType->first();
    }

    /**
     * @param string $recordType
     * @return int|null
     */
    public function getTrustedDays(string $recordType): ?int
    {
        /** @var FundConfigRecord $typeConfig */
        $typeConfig = $this->fund_config_records->where('record_type', $recordType)->first();
        $typeConfigValue = $typeConfig ? $typeConfig->record_validity_days : null;
        $fundConfigValue = $this->fund_config ? $this->fund_config->record_validity_days : null;

        if ($typeConfigValue === 0) {
            return $typeConfigValue;
        } else if ($typeConfigValue === null && $fundConfigValue === 0) {
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

                    return is_numeric(
                        $record['value']
                    ) ? $formula->amount * $record['value'] : 0;
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
            $records = $this->getTrustedRecordOfType(
                $identityAddress,
                $multiplier->record_type_key
            );

            return ((int) ($records ? $records['value']: 1)) * $multiplier->multiplier;
        })->sum();
    }

    /**
     * @param Request $request
     * @param Builder $query
     * @return Builder
     */
    public static function search(
        Request $request,
        Builder $query
    ): Builder {
        if (is_null($query)) {
            /** @var Builder $newQuery */
            $newQuery = self::query();
            $query = $newQuery;
        }

        if ($request->has('tag')) {
            $query->whereHas('tags', static function(Builder $query) use ($request) {
                return $query->where('key', $request->input('tag'));
            });
        }

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->input('organization_id'));
        }

        if ($request->has('fund_id')) {
            $query->where('id', $request->input('fund_id'));
        }

        if ($request->has('q') && !empty($q = $request->input('q'))) {
            $query = FundQuery::whereQueryFilter($query, $q);
        }

        if ($request->has('implementation_id')) {
            $query = FundQuery::whereImplementationIdFilter(
                $query,
                $request->input('implementation_id')
            );
        }

        return $query;
    }

    /**
     * @return mixed|null
     */
    public function amountFixedByFormula()
    {
        if (!$fundFormula = $this->fund_formulas) {
            return null;
        }

        if($fundFormula->filter(static function (FundFormula $formula){
            return $formula->type !== 'fixed';
        })->count()){
            return null;
        }

        return $fundFormula->sum('amount');
    }

    /**
     * @return BunqService|string
     */
    public function getBunq() {
        $fundBunq = $this->getBunqKey();

        if (empty($fundBunq) || empty($fundBunq['key'])) {
            return false;
        }

        return BunqService::create(
            $this->id,
            $fundBunq['key'],
            $fundBunq['allowed_ip'],
            $fundBunq['sandbox']
        );
    }

    /**
     * @return Fund[]|Builder[]|Collection|\Illuminate\Support\Collection
     */
    public static function configuredFunds() {
        try {
            return static::query()->whereHas('fund_config')->get();
        } catch (\Exception $exception) {
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
        /** @var Collection|Fund[] $funds */
        $funds = self::query()->whereHas('fund_config', static function (Builder $query) {
            return $query->where('is_configured', true);
        })->whereDate('start_date', '<=', now())->get();

        foreach($funds as $fund) {
            if ($fund->state === self::STATE_PAUSED &&
                $fund->start_date->startOfDay()->isPast()) {
                $fund->changeState(self::STATE_ACTIVE);
                FundStartedEvent::dispatch($fund);
            }

            $expirationNotified = $fund->logs()->where([
                'event' => self::EVENT_FUND_EXPIRING
            ])->exists();

            if (!$expirationNotified && $fund->state !== self::STATE_CLOSED &&
                $fund->end_date->clone()->subDays(14)->isPast()) {
                FundExpiringEvent::dispatch($fund);
            }

            if ($fund->state !== self::STATE_CLOSED && $fund->end_date->isPast()) {
                $fund->changeState(self::STATE_CLOSED);
                FundEndedEvent::dispatch($fund);
            }
        }
    }

    /**
     * @return void
     */
    public static function checkConfigStateQueue(): void
    {
        $funds = self::query()->whereHas('fund_config', static function (Builder $query) {
            return $query->where('is_configured', true);
        })->where([
            'state' => self::STATE_WAITING
        ])->whereDate('start_date', '>=', now())->get();

        /** @var self $fund */
        foreach($funds as $fund) {
            $fund->changeState(self::STATE_PAUSED);

            $fund->criteria()->create([
                'record_type_key' => $fund->fund_config->key . '_eligible',
                'value' => "Ja",
                'operator' => '='
            ]);

            $fund->criteria()->create([
                'record_type_key' => 'children_nth',
                'value' => 1,
                'operator' => '>='
            ]);

            foreach ($fund->provider_organizations_approved as $organization) {
                resolve('forus.services.notification')->newFundStarted(
                    $organization->email,
                    $fund->fund_config->implementation->getEmailFrom(),
                    $fund->name,
                    $fund->organization->name
                );
            }
        }
    }

    /**
     * Send funds user count statistic to email
     * @param string $email
     * @return void
     */
    public static function sendUserStatisticsReport(string $email): void {
        /** @var Collection|Fund[] $funds */
        $funds = self::query()->whereHas('fund_config', static function (Builder $query) {
            return $query->where('is_configured', true);
        })->whereIn('state', [
            self::STATE_ACTIVE, self::STATE_PAUSED
        ])->get();

        if ($funds->count() === 0) {
            return;
        }

        foreach($funds as $fund) {
            $organization = $fund->organization;
            $sponsorCount = $organization->employees->count();

            $providersQuery = FundProviderQuery::whereApprovedForFundsFilter(
                FundProvider::query(), $fund->id
            );

            $providerCount = $providersQuery->get()->map(static function ($fundProvider){
                /** @var FundProvider $fundProvider */
                return $fundProvider->organization->employees->count();
            })->sum();

            if ($fund->state === self::STATE_ACTIVE) {
                $requesterCount = $fund->vouchers()->whereNull('parent_id')->count();
            } else {
                $requesterCount = 0;
            }

            resolve('forus.services.notification')->sendFundUserStatisticsReport(
                $email,
                $fund->fund_config->implementation->getEmailFrom(),
                $fund->name,
                $organization->name,
                $sponsorCount,
                $providerCount,
                $requesterCount
            );
        }
    }

    /**
     * @return void
     */
    public static function notifyAboutReachedNotificationAmount(): void
    {
        /** @var NotificationService $mailService */
        $mailService = resolve('forus.services.notification');

        /** @var RecordRepo $recordRepo */
        $recordRepo = resolve('forus.services.record');

        $funds = self::query()
            ->whereHas('fund_config', static function (Builder $query){
                return $query->where('is_configured', true);
            })
            ->where(static function (Builder $query){
                return $query->whereNull('notified_at')
                    ->orWhereDate('notified_at', '<=', now()->subDays(
                        7
                    )->startOfDay());
            })
            ->where('state', 'active')
            ->where('notification_amount', '>', 0)
            ->whereNotNull('notification_amount')
            ->with('organization')
            ->get();

        /** @var self $fund */
        foreach ($funds as $fund) {
            $transactionCosts = $fund->getTransactionCosts();

            if ($fund->budget_left - $transactionCosts <= $fund->notification_amount) {
                FundBalanceLowEvent::dispatch($fund);

                $referrers = $fund->organization->employeesOfRole('finance');
                $referrers = $referrers->pluck('identity_address');
                $referrers = $referrers->push(
                    $fund->organization->identity_address
                )->map(static function ($identity) use ($recordRepo) {
                    return [
                        'identity' => $identity,
                        'email' => $recordRepo->primaryEmailByAddress($identity),
                    ];
                })->push([
                    'identity' => null,
                    'email' => $fund->organization->email
                ])->unique('email');

                foreach ($referrers as $referrer) {
                    $mailService->fundBalanceWarning(
                        $referrer['email'],
                        $fund->fund_config->implementation->getEmailFrom(),
                        config('forus.front_ends.panel-sponsor'),
                        $fund->organization->name,
                        $fund->name,
                        currency_format($fund->notification_amount - $transactionCosts),
                        currency_format($fund->budget_left)
                    );
                }
            }
        }
    }

    /**
     * @param float $amount
     * @param string $description
     * @param string|null $issuer
     * @return \Illuminate\Database\Eloquent\Model|BunqMeTab
     * @throws \Exception
     */
    public function makeBunqMeTab(
        float $amount,
        string $description = '',
        string $issuer = null
    ): BunqMeTab {
        $tabRequest = $this->getBunq()->makeBunqMeTabRequest($amount, $description);
        $bunqMeTab = $tabRequest->getBunqMeTab();
        $amountInquired = $bunqMeTab->getBunqmeTabEntry()->getAmountInquired();
        $description = $bunqMeTab->getBunqmeTabEntry()->getDescription();
        $issuer_auth_url = null;

        if ($issuer && env('BUNQ_IDEAL_USE_ISSUERS', true)) {
            $request = $tabRequest->makeIdealIssuerRequest($issuer);
            $issuer_auth_url = $request ? $request->getUrl() : null;
        }

        return $this->bunq_me_tabs()->create([
            'bunq_me_tab_id'            => $bunqMeTab->getId(),
            'status'                    => $bunqMeTab->getStatus(),
            'monetary_account_id'       => $bunqMeTab->getMonetaryAccountId(),
            'amount'                    => $amountInquired->getValue(),
            'description'               => $description,
            'uuid'                      => $tabRequest->getUuid(),
            'share_url'                 => $tabRequest->getShareUrl(),
            'issuer_authentication_url' => $issuer_auth_url,
        ]);
    }

    /**
     * @param string|null $identity_address
     * @param float|null $voucherAmount
     * @param Carbon|null $expire_at
     * @param string|null $note
     * @return Voucher|null
     */
    public function makeVoucher(
        string $identity_address = null,
        float $voucherAmount = null,
        Carbon $expire_at = null,
        string $note = null
    ): ?Voucher {
        $amount = $voucherAmount ?: $this->amountForIdentity($identity_address);
        $returnable = false;
        $expire_at = $expire_at ?: $this->end_date;
        $fund_id = $this->id;
        $limit_multiplier = $this->multiplierForIdentity($identity_address);
        $voucher = null;

        if ($this->fund_formulas->count() > 0) {
            $voucher = Voucher::create(compact(
                'identity_address', 'amount', 'expire_at', 'note', 'fund_id',
                'returnable', 'limit_multiplier'
            ));

            VoucherCreated::dispatch($voucher);
        }

        if ($this->fund_formula_products->count() > 0) {
            foreach ($this->fund_formula_products as $fund_formula_product) {
                $voucherExpireAt = $fund_formula_product->product->expire_at && $this->end_date->gt(
                    $fund_formula_product->product->expire_at
                ) ? $fund_formula_product->product->expire_at : $this->end_date;

                $voucher = $this->makeProductVoucher(
                    $identity_address,
                    $fund_formula_product->product->id,
                    $voucherExpireAt,
                    '',
                    $fund_formula_product->price
                );

                VoucherAssigned::broadcast($voucher);
            }
        }

        return $voucher;
    }

    /**
     * @param string|null $identity_address
     * @param int|null $product_id
     * @param Carbon|null $expire_at
     * @param string|null $note
     * @param float|null $price
     * @return Voucher
     */
    public function makeProductVoucher(
        string $identity_address = null,
        int $product_id = null,
        Carbon $expire_at = null,
        string $note = null,
        float $price = null
    ): Voucher {
        $amount = $price ?: Product::findOrFail($product_id)->price;
        $expire_at = $expire_at ?: $this->end_date;
        $fund_id = $this->id;
        $returnable = false;

        $voucher = Voucher::create(compact(
            'identity_address', 'amount', 'expire_at', 'note',
            'product_id','fund_id', 'returnable'
        ));

        VoucherCreated::dispatch($voucher, false);

        return $voucher;
    }

    /**
     * @param FundCriterion|null $fundCriterion
     * @return array
     */
    public function validatorEmployees(
        ?FundCriterion $fundCriterion = null
    ): array {
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
     * @param string $identity_address
     * @param array $records
     * @return FundRequest
     */
    public function makeFundRequest(
        string $identity_address,
        array $records
    ): FundRequest {
        /** @var FundRequest $fundRequest */
        $fundRequest = $this->fund_requests()->create(compact(
            'identity_address'
        ));

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
    public function syncCriteria(array $criteria): self {
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
     * @return \App\Models\FundTopUp
     * @noinspection PhpUnused
     */
    public function getTopUpModelAttribute(): FundTopUp
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
     * @return EmailFrom
     */
    public function getEmailFrom(): EmailFrom {
        return $this->fund_config->implementation->getEmailFrom() ??
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
     * @param ?string $identity_address
     * @return bool
     */
    public function isTakenByPartner(?string $identity_address): bool {
        if (!$identity_address || !$identity = Identity::findByAddress($identity_address)) {
            return false;
        }

        return Identity::whereHas('vouchers', function(Builder $builder) {
            return VoucherQuery::whereNotExpired($builder->where('fund_id', $this->id));
        })->whereHas('records', function(Builder $builder) use ($identity) {
            $builder->where(function(Builder $builder) use ($identity) {
                $identityBsn = record_repo()->bsnByAddress($identity->address);

                $builder->where(function(Builder $builder) use ($identity, $identityBsn) {
                    $builder->where('record_type_id', record_repo()->getTypeIdByKey(
                        $this->isHashingBsn() ? 'partner_bsn_hash': 'partner_bsn'
                    ));

                    $builder->whereIn('value', $this->isHashingBsn() ? array_filter([
                        $this->getTrustedRecordOfType($identity->address, 'bsn_hash')['value'] ?? null,
                        $identityBsn ? $this->getHashedValue($identityBsn) : null
                    ]) : [$identityBsn ?: null]);
                });

                $builder->orWhere(function(Builder $builder) use ($identity, $identityBsn) {
                    $builder->where('record_type_id', record_repo()->getTypeIdByKey(
                        $this->isHashingBsn() ? 'bsn_hash': 'bsn'
                    ));

                    $builder->whereIn('value', $this->isHashingBsn() ? array_filter([
                        $this->getTrustedRecordOfType($identity->address, 'partner_bsn_hash')['value'] ?? null,
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
     * @return bool
     */
    public function limitGeneratorAmount(): bool
    {
        return $this->fund_config && $this->fund_config->limit_generator_amount ?? true;
    }

    /**
     * @return float
     */
    public function getMaxAmountPerVoucher(): float
    {
        $max_allowed = config('forus.funds.max_sponsor_voucher_amount');
        $max = min($this->budget_left ?? $max_allowed, $max_allowed);

        return (float) ($this->limitGeneratorAmount() ? $max : $max_allowed);
    }

    /**
     * @return float
     */
    public function getMaxAmountSumVouchers(): float
    {
        return (float) ($this->limitGeneratorAmount() ? $this->budget_left : 1000000);
    }
}
