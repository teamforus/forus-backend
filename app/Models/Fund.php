<?php

namespace App\Models;

use App\Events\Funds\FundBalanceLowEvent;
use App\Events\Funds\FundEndedEvent;
use App\Events\Funds\FundExpiringEvent;
use App\Events\Funds\FundStartedEvent;
use App\Events\Vouchers\VoucherCreated;
use App\Services\EventLogService\Traits\HasDigests;
use App\Services\EventLogService\Traits\HasLogs;
use App\Models\Traits\HasTags;
use App\Scopes\Builders\FundProviderQuery;
use App\Services\BunqService\BunqService;
use App\Services\FileService\Models\File;
use App\Services\Forus\Notification\NotificationService;
use App\Services\Forus\Record\Repositories\RecordRepo;
use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Traits\HasMedia;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Http\Request;

/**
 * App\Models\Fund
 *
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string|null $description
 * @property string $state
 * @property bool $public
 * @property float|null $notification_amount
 * @property \Illuminate\Support\Carbon|null $notified_at
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $default_validator_employee_id
 * @property bool $auto_requests_validation
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\BunqMeTab[] $bunq_me_tabs
 * @property-read int|null $bunq_me_tabs_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\BunqMeTab[] $bunq_me_tabs_paid
 * @property-read int|null $bunq_me_tabs_paid_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundCriterion[] $criteria
 * @property-read int|null $criteria_count
 * @property-read \App\Models\Employee|null $default_validator_employee
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Employee[] $employees
 * @property-read int|null $employees_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Employee[] $employees_validators
 * @property-read int|null $employees_validators_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Product[] $formula_products
 * @property-read int|null $formula_products_count
 * @property-read \App\Models\FundConfig $fund_config
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundFormulaProduct[] $fund_formula_products
 * @property-read int|null $fund_formula_products_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundFormula[] $fund_formulas
 * @property-read int|null $fund_formulas_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundRequest[] $fund_requests
 * @property-read int|null $fund_requests_count
 * @property-read float $budget_left
 * @property-read float $budget_total
 * @property-read float $budget_used
 * @property-read float $budget_validated
 * @property-read \App\Models\FundTopUp $top_up_model
 * @property-read \App\Services\MediaService\Models\Media $logo
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\EventLogService\Models\EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\MediaService\Models\Media[] $medias
 * @property-read int|null $medias_count
 * @property-read \App\Models\Organization $organization
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Product[] $products
 * @property-read int|null $products_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundProviderInvitation[] $provider_invitations
 * @property-read int|null $provider_invitations_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Organization[] $provider_organizations
 * @property-read int|null $provider_organizations_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Organization[] $provider_organizations_approved
 * @property-read int|null $provider_organizations_approved_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Organization[] $provider_organizations_approved_budget
 * @property-read int|null $provider_organizations_approved_budget_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Organization[] $provider_organizations_approved_products
 * @property-read int|null $provider_organizations_approved_products_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Organization[] $provider_organizations_declined
 * @property-read int|null $provider_organizations_declined_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Organization[] $provider_organizations_pending
 * @property-read int|null $provider_organizations_pending_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundProvider[] $providers
 * @property-read int|null $providers_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundProvider[] $providers_allowed_products
 * @property-read int|null $providers_allowed_products_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundProvider[] $providers_approved
 * @property-read int|null $providers_approved_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundProvider[] $providers_declined_products
 * @property-read int|null $providers_declined_products_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Tag[] $tags
 * @property-read int|null $tags_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundTopUpTransaction[] $top_up_transactions
 * @property-read int|null $top_up_transactions_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundTopUp[] $top_ups
 * @property-read int|null $top_ups_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\VoucherTransaction[] $voucher_transactions
 * @property-read int|null $voucher_transactions_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Voucher[] $vouchers
 * @property-read int|null $vouchers_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fund newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fund newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fund query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fund whereAutoRequestsValidation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fund whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fund whereDefaultValidatorEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fund whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fund whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fund whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fund whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fund whereNotificationAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fund whereNotifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fund whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fund wherePublic($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fund whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fund whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fund whereUpdatedAt($value)
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

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organization_id', 'state', 'name', 'description', 'start_date',
        'end_date', 'notification_amount', 'fund_id', 'notified_at', 'public',
        'default_validator_employee_id', 'auto_requests_validation'
    ];

    protected $hidden = [
        'fund_config', 'fund_formulas'
    ];

    protected $casts = [
        'public' => 'boolean',
        'auto_requests_validation' => 'boolean',
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
    public function organization() {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function products() {
        return $this->belongsToMany(
            Product::class,
            'fund_products'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function criteria() {
        return $this->hasMany(FundCriterion::class);
    }

    /**
     * Get fund logo
     * @return MorphOne
     */
    public function logo() {
        return $this->morphOne(Media::class, 'mediable')->where([
            'type' => 'fund_logo'
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function vouchers() {
        return $this->hasMany(Voucher::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function voucher_transactions() {
        return $this->hasManyThrough(VoucherTransaction::class, Voucher::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function providers() {
        return $this->hasMany(FundProvider::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function providers_approved() {
        return $this->hasMany(FundProvider::class)->where(function(Builder $builder) {
            $builder->where('allow_budget', true);
            $builder->orWhere('allow_products', true);
            $builder->orWhere('allow_some_products', true);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function provider_invitations() {
        return $this->hasMany(FundProviderInvitation::class, 'from_fund_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function providers_allowed_products() {
        return $this->hasMany(FundProvider::class)->where([
            'allow_products' => true
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function providers_declined_products() {
        return $this->hasMany(FundProvider::class)->where([
            'allow_products' => false
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function top_ups() {
        return $this->hasMany(FundTopUp::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function default_validator_employee() {
        return $this->belongsTo(Employee::class, 'default_validator_employee_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function top_up_transactions() {
        return $this->hasManyThrough(FundTopUpTransaction::class, FundTopUp::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fund_requests() {
        return $this->hasMany(FundRequest::class);
    }

    /**
     * @return float
     */
    public function getBudgetValidatedAttribute() {
        return 0;
    }

    /**
     * @return float
     */
    public function getBudgetTotalAttribute() {
        return round(array_sum([
            $this->top_up_transactions->sum('amount'),
            $this->bunq_me_tabs_paid->sum('amount')
        ]), 2);
    }

    /**
     * @return float
     */
    public function getBudgetUsedAttribute() {
        return round($this->voucher_transactions->sum('amount'), 2);
    }

    /**
     * @return float
     */
    public function getBudgetLeftAttribute() {
        return round($this->budget_total - $this->budget_used, 2);
    }

    public function getFundId() {
        return $this->id;
    }

    public function getServiceCosts(): float
    {
        return $this->getTransactionCosts();
    }

    public function getTransactionCosts (): float
    {
        if ($this->fund_config && !$this->fund_config->subtract_transaction_costs) {
            return $this
                    ->voucher_transactions()
                    ->count() * 0.10;
        }

        return 0.0;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function provider_organizations_approved() {
        return $this->belongsToMany(
            Organization::class,
            'fund_providers'
        )->where(function(Builder $builder) {
            $builder->where('allow_budget', true);
            $builder->orWhere('allow_products', true);
            $builder->orWhere('allow_some_products', true);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function provider_organizations_approved_budget() {
        return $this->belongsToMany(
            Organization::class,
            'fund_providers'
        )->where(function(Builder $builder) {
            $builder->where('allow_budget', true);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function provider_organizations_approved_products() {
        return $this->belongsToMany(
            Organization::class,
            'fund_providers'
        )->where(function(Builder $builder) {
            $builder->where('allow_products', true);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function provider_organizations_declined() {
        return $this->belongsToMany(
            Organization::class,
            'fund_providers'
        )->where([
            'allow_budget' => false,
            'allow_products' => false,
            'dismissed' => true,
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function provider_organizations_pending() {
        return $this->belongsToMany(
            Organization::class,
            'fund_providers'
        )->where([
            'allow_budget' => false,
            'allow_products' => false,
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function provider_organizations() {
        return $this->belongsToMany(
            Organization::class,
            'fund_providers'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function employees() {
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
     */
    public function employees_validators() {
        return $this->hasManyThrough(
            Employee::class,
            Organization::class,
            'id',
            'organization_id',
            'organization_id',
            'id'
        )->whereHas('roles.permissions', function(Builder $builder) {
            $builder->where('key', 'validate_records');
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function fund_config() {
        return $this->hasOne(FundConfig::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fund_formulas() {
        return $this->hasMany(FundFormula::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fund_formula_products() {
        return $this->hasMany(FundFormulaProduct::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function formula_products() {
        return $this->hasManyThrough(
            Product::class,
            FundFormulaProduct::class,
            'fund_id',
            'id'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function bunq_me_tabs() {
        return $this->hasMany(BunqMeTab::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function bunq_me_tabs_paid() {
        return $this->hasMany(BunqMeTab::class)->where([
            'status' => 'PAID'
        ]);
    }

    /**
     * @return array|null
     */
    public function getBunqKey() {
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
     * @param Fund $fund
     * @param string $identity_address
     * @param string $recordType
     * @param Organization|null $organization
     * @return mixed
     */
    public static function getTrustedRecordOfType(
        Fund $fund,
        string $identity_address,
        string $recordType,
        Organization $organization = null
    ) {
        $recordRepo = resolve('forus.services.record');
        $trustedIdentities = $fund->validatorEmployees();

        /** @var FundCriterion $criterion */
        $recordsOfType = collect($recordRepo->recordsList(
            $identity_address, $recordType, null
        ));

        $validRecordsOfType = $recordsOfType->map(function($record) use (
            $trustedIdentities, $organization
        ) {
            $validations = collect($record['validations'])->whereIn(
                'identity_address', $trustedIdentities);

            if ($organization) {
                $validations = collect()->merge($validations->where(
                    'organization_id', $organization->id
                ))->merge($validations->where(
                    'organization_id', null
                ));
            }

            return array_merge($record, [
                'validations' => $validations->sortByDesc('created_at')
            ]);
        })->filter(function($record) {
            return count($record['validations']) > 0;
        })->sortByDesc(function($record) {
            return $record['validations'][0]['created_at'];
        });

        return collect($validRecordsOfType)->first();
    }

    /**
     * @param Fund $fund
     * @param $identityAddress
     * @return int|mixed
     */
    public static function amountForIdentity(Fund $fund, $identityAddress)
    {
        if ($fund->fund_formulas->count() == 0 &&
            $fund->fund_formula_products->pluck('price')->sum() == 0) {
            return 0;
        }

        return $fund->fund_formulas->map(function(FundFormula $formula) use (
                $fund, $identityAddress
            ) {
                switch ($formula->type) {
                    case 'fixed': return $formula->amount; break;
                    case 'multiply': {
                        $record = self::getTrustedRecordOfType(
                            $fund,
                            $identityAddress,
                            $formula->record_type_key,
                            $fund->organization
                        );

                        return is_numeric(
                            $record['value']
                        ) ? $formula->amount * $record['value'] : 0;
                    } break;
                    default: return 0; break;
                }
            })->sum() + $fund->fund_formula_products->pluck('price')->sum();
    }

    /**
     * @param Request $request
     * @param Builder|null $query
     * @return Builder
     */
    public static function search(
        Request $request,
        Builder $query = null
    ) {
        $query = $query ?: self::query();

        if ($request->has('tag')) {
            $query->whereHas('tags', function(Builder $query) use ($request) {
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
            $query->where('name', 'LIKE', "%{$q}%");
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

        if($fundFormula->filter(function (FundFormula $formula){
            return $formula->type != 'fixed';
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

    public static function configuredFunds () {
        try {
            return static::query()->whereHas('fund_config')->get();
        } catch (\Exception $exception) {
            return collect();
        }
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function requiredPrevalidationKeys() {
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
    public function changeState(string $state) {
        if (in_array($state, self::STATES)) {
            $this->update(compact('state'));
        }

        return $this;
    }

    /**
     * Update fund state by the start and end dates
     */
    public static function checkStateQueue() {
        /** @var Collection|Fund[] $funds */
        $funds = self::query()
            ->whereHas('fund_config', function (Builder $query) {
                return $query->where('is_configured', true);
            })
            ->whereDate('start_date', '<=', now())
            ->get();

        foreach($funds as $fund) {
            if ($fund->start_date->startOfDay()->isPast() &&
                $fund->state == self::STATE_PAUSED) {
                $fund->changeState(self::STATE_ACTIVE);
                FundStartedEvent::dispatch($fund);
            }

            $expirationNotified = $fund->logs()->where([
                'event' => Fund::EVENT_FUND_EXPIRING
            ])->exists();

            if (!$expirationNotified &&
                $fund->end_date->endOfDay()->clone()->subDays(14)->isPast() &&
                $fund->state != self::STATE_CLOSED) {
                FundExpiringEvent::dispatch($fund);
            }

            if ($fund->end_date->endOfDay()->isPast() &&
                $fund->state != self::STATE_CLOSED) {
                $fund->changeState(self::STATE_CLOSED);
                FundEndedEvent::dispatch($fund);
            }
        }
    }

    /**
     * @return void
     */
    public static function checkConfigStateQueue()
    {
        $funds = self::query()
            ->whereHas('fund_config', function (Builder $query) {
                return $query->where('is_configured', true);
            })
            ->where('state', Fund::STATE_WAITING)
            ->whereDate('start_date', '>', now())
            ->get();

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
    public static function sendUserStatisticsReport(string $email)
    {
        /** @var Collection|Fund[] $funds */
        $funds = self::query()->whereHas('fund_config', function (
            Builder $query
        ) {
            return $query->where('is_configured', true);
        })->whereIn('state', [
            self::STATE_ACTIVE,
            self::STATE_PAUSED,
        ])->get();

        if ($funds->count() == 0) {
            return null;
        }

        foreach($funds as $fund) {
            $organization = $fund->organization;
            $sponsorCount = $organization->employees->count() + 1;

            $providersQuery = FundProviderQuery::whereApprovedForFundsFilter(
                FundProvider::query(), $fund->id
            );

            $providerCount = $providersQuery->get()->map(function ($fundProvider){
                /** @var FundProvider $fundProvider */
                return $fundProvider->organization->employees->count() + 1;
            })->sum();

            if ($fund->state == self::STATE_ACTIVE) {
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
    public static function notifyAboutReachedNotificationAmount()
    {
        /** @var NotificationService $mailService */
        $mailService = resolve('forus.services.notification');

        /** @var RecordRepo $recordRepo */
        $recordRepo = resolve('forus.services.record');

        $funds = self::query()
            ->whereHas('fund_config', function (Builder $query){
                return $query->where('is_configured', true);
            })
            ->where(function (Builder $query){
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
                )->map(function ($identity) use ($recordRepo) {
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

                $fund->update([
                    'notified_at' => now()
                ]);
            }
        }
    }

    /**
    * @param Collection|Fund[] $funds
    * @return mixed
    */
    public static function sortByState($funds) {
        return $funds->sort(function (Fund $fund1, Fund $fund2) {
            if ($fund1->state == $fund2->state) {
                return 0;
            }

            return !in_array($fund1->state, [
                Fund::STATE_ACTIVE,
                Fund::STATE_PAUSED,
            ]);
        });
    }

    /**
     * @param float $amount
     * @param string $description
     * @param string|null $issuer
     * @return \Illuminate\Database\Eloquent\Model
     * @throws \Exception
     */
    public function makeBunqMeTab(
        float $amount,
        string $description = '',
        string $issuer = null
    ) {
        $tabRequest = $this->getBunq()->makeBunqMeTabRequest(
            $amount,
            $description
        );

        $bunqMeTab = $tabRequest->getBunqMeTab();
        $amount = $bunqMeTab->getBunqmeTabEntry()->getAmountInquired();
        $description = $bunqMeTab->getBunqmeTabEntry()->getDescription();
        $issuer_auth_url = null;


        if (env('BUNQ_IDEAL_USE_ISSUERS', true) && $issuer) {
            $issuer_auth_url = $tabRequest->makeIdealIssuerRequest(
                $issuer
            )->getUrl();
        }

        return $this->bunq_me_tabs()->create([
            'bunq_me_tab_id'            => $bunqMeTab->getId(),
            'status'                    => $bunqMeTab->getStatus(),
            'monetary_account_id'       => $bunqMeTab->getMonetaryAccountId(),
            'amount'                    => $amount->getValue(),
            'description'               => $description,
            'uuid'                      => $tabRequest->getUuid(),
            'share_url'                 => $tabRequest->getShareUrl(),
            'issuer_authentication_url' => $issuer_auth_url
        ]);
    }

    /**
     * @param string|null $identity_address
     * @param float|null $voucherAmount
     * @param Carbon|null $expire_at
     * @param string|null $note
     * @return Voucher|\Illuminate\Database\Eloquent\Model
     */
    public function makeVoucher(
        string $identity_address = null,
        float $voucherAmount = null,
        Carbon $expire_at = null,
        string $note = null
    ) {
        $amount = $voucherAmount ?: self::amountForIdentity(
            $this,
            $identity_address
        );

        $returnable = false;
        $expire_at = $expire_at ?: $this->end_date;
        $fund_id = $this->id;

        $voucher = Voucher::create(compact(
            'identity_address', 'amount', 'expire_at', 'note',
            'fund_id', 'returnable'
        ));

        VoucherCreated::dispatch($voucher);

        if ($voucherAmount === null) {
            foreach ($this->fund_formula_products as $fund_formula_product) {
                $voucher->buyProductVoucher(
                    $fund_formula_product->product,
                    $fund_formula_product->amount,
                    false
                );
            }
        }

        return $voucher;
    }

    /**
     * @param string|null $identity_address
     * @param int|null $product_id
     * @param Carbon|null $expire_at
     * @param string|null $note
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function makeProductVoucher(
        string $identity_address = null,
        int $product_id = null,
        Carbon $expire_at = null,
        string $note = null
    ) {
        $amount = 0;
        $expire_at = $expire_at ?: $this->end_date;
        $fund_id = $this->id;

        $voucher = Voucher::create(compact(
            'identity_address', 'amount', 'expire_at', 'note', 'product_id',
            'fund_id'
        ));

        VoucherCreated::dispatch($voucher);

        return $voucher;
    }

    /**
     * @param bool $force_fetch
     * @return array
     */
    public function validatorEmployees(bool $force_fetch = true) {
        return ($force_fetch ? $this->employees_validators() :
            $this->employees_validators)
            ->pluck('employees.identity_address')->toArray();
    }

    /**
     * @param string $identity_address
     * @param array $records
     * @return FundRequest
     */
    public function makeFundRequest(string $identity_address, array $records)
    {
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
     * Store criteria for newly created fund
     * @param array $criteria
     * @return $this
     */
    public function makeCriteria(array $criteria)
    {
        $this->criteria()->createMany(array_map(function($criterion) {
            return array_only($criterion, [
                'record_type_key', 'operator', 'value', 'show_attachment',
                'description'
            ]);
        }, $criteria));

        return $this;
    }

    /**
     * Update criteria for existing fund
     * @param array $criteria
     * @return $this
     */
    public function updateCriteria(array $criteria)
    {
        $this->criteria()->whereNotIn('id', array_filter(
            array_pluck($criteria, 'id'), function($id) {
            return !empty($id);
        }))->delete();

        foreach ($criteria as $criterion) {
            /** @var FundCriterion|null $db_criteria */
            $data_criteria = array_only($criterion, [
                'record_type_key', 'operator', 'value', 'show_attachment',
                'description'
            ]);

            if ($db_criteria = $this->criteria()->find($criterion['id'] ?? null)) {
                $db_criteria->update($data_criteria);
            } else {
                $this->criteria()->create($data_criteria);
            }
        }

        return $this;
    }

    /**
     * @param array $productIds
     * @return $this
     */
    public function updateFormulaProducts(array $productIds)
    {
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
     * @return mixed|string
     */
    public function urlWebshop(string $uri = "/")
    {
        return $this->fund_config->implementation->urlWebshop($uri);
    }

    /**
     * @param string $uri
     * @return mixed|string
     */
    public function urlSponsorDashboard(string $uri = "/")
    {
        return $this->fund_config->implementation->urlSponsorDashboard($uri);
    }

    /**
     * @param string $uri
     * @return mixed|string
     */
    public function urlProviderDashboard(string $uri = "/")
    {
        return $this->fund_config->implementation->urlProviderDashboard($uri);
    }

    /**
     * @param string $uri
     * @return mixed|string
     */
    public function urlValidatorDashboard(string $uri = "/")
    {
        return $this->fund_config->implementation->urlValidatorDashboard($uri);
    }

    /**
     * @return \App\Models\FundTopUp
     */
    public function getTopUpModelAttribute()
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
}
