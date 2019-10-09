<?php

namespace App\Models;

use App\Events\Vouchers\VoucherCreated;
use App\Services\BunqService\BunqService;
use App\Services\Forus\Notification\NotificationService;
use App\Services\Forus\Record\Repositories\RecordRepo;
use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Traits\HasMedia;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * App\Models\Fund
 *
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string $state
 * @property bool $public
 * @property float|null $notification_amount
 * @property \Illuminate\Support\Carbon|null $notified_at
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\BunqMeTab[] $bunq_me_tabs
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\BunqMeTab[] $bunq_me_tabs_paid
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundCriterion[] $criteria
 * @property-read \App\Models\FundConfig $fund_config
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundFormula[] $fund_formulas
 * @property-read float $budget_left
 * @property-read float $budget_total
 * @property-read float $budget_used
 * @property-read float $budget_validated
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @property-read \App\Services\MediaService\Models\Media $logo
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\MediaService\Models\Media[] $medias
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundMeta[] $metas
 * @property-read \App\Models\Organization $organization
 * @property-read \Kalnoy\Nestedset\Collection|\App\Models\ProductCategory[] $product_categories
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Product[] $products
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Organization[] $provider_organizations
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Organization[] $provider_organizations_approved
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Organization[] $provider_organizations_declined
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Organization[] $provider_organizations_pending
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundProvider[] $providers
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundTopUpTransaction[] $top_up_transactions
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundTopUp[] $top_ups
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Validator[] $validators
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\VoucherTransaction[] $voucher_transactions
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Voucher[] $vouchers
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fund newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fund newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fund query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Fund whereCreatedAt($value)
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
    use HasMedia;

    const STATE_ACTIVE = 'active';
    const STATE_CLOSED = 'closed';
    const STATE_PAUSED = 'paused';
    const STATE_WAITING = 'waiting';

    const STATES = [
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
        'organization_id', 'state', 'name', 'start_date', 'end_date',
        'notification_amount', 'fund_id', 'notified_at', 'public'
    ];

    protected $hidden = [
        'fund_config', 'fund_formulas'
    ];

    protected $casts = [
        'public' => 'boolean',
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function metas() {
        return $this->hasMany(FundMeta::class);
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function product_categories() {
        return $this->belongsToMany(
            ProductCategory::class,
            'fund_product_categories'
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
    public function top_ups() {
        return $this->hasMany(FundTopUp::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function top_up_transactions() {
        return $this->hasManyThrough(FundTopUpTransaction::class, FundTopUp::class);
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
        )->where('fund_providers.state', 'approved');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function provider_organizations_declined() {
        return $this->belongsToMany(
            Organization::class,
            'fund_providers'
        )->where('fund_providers.state', 'declined');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function provider_organizations_pending() {
        return $this->belongsToMany(
            Organization::class,
            'fund_providers'
        )->where('fund_providers.state', 'pending');
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
    public function validators() {
        return $this->hasManyThrough(
            Validator::class,
            Organization::class,
            'id',
            'organization_id',
            'organization_id',
            'id'
        );
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
        $recordRepo = app()->make('forus.services.record');

        $trustedIdentities = $fund->validators->pluck(
            'identity_address'
        );

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
        if (!$fundFormula = $fund->fund_formulas) {
            return 0;
        }

        return $fundFormula->map(function(FundFormula $formula) use (
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
        })->sum();
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

        $bunqService = BunqService::create(
            $this->id,
            $fundBunq['key'],
            $fundBunq['allowed_ip'],
            $fundBunq['sandbox']
        );

        return $bunqService;
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

        /** @var self $fund */
        foreach($funds as $fund) {
            if ($fund->start_date->startOfDay()->isPast() &&
                $fund->state == self::STATE_PAUSED) {
                $fund->changeState(self::STATE_ACTIVE);

                /*
                $organizations = Organization::query()->whereIn(
                    'id', OrganizationProductCategory::query()->whereIn(
                    'product_category_id',
                    $fund->product_categories()->pluck('id')->all()
                )->pluck('organization_id')->toArray()
                )->get();
                */

                /** @var Organization $organization */
                // TODO: Notify providers about new fund started
                
                /*
                foreach ($organizations as $organization) {
                    resolve('forus.services.mail_notification')->newFundStarted(
                        $organization->email,
                        $organization->emailServiceId(),
                        $fund->name,
                        $fund->organization->name
                    );
                }
                */
            }

            if ($fund->end_date->endOfDay()->isPast() &&
                $fund->state != self::STATE_CLOSED) {
                $fund->changeState(self::STATE_CLOSED);
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

            /*$organizations = Organization::query()->whereIn(
                'id', OrganizationProductCategory::query()->whereIn(
                'product_category_id',
                $fund->product_categories()->pluck('id')->all()
            )->pluck('organization_id')->toArray()
            )->get();*/

            /** @var Organization $organization */
            // TODO: Notify providers about new fund applicable
            /*foreach ($organizations as $organization) {
                resolve('forus.services.mail_notification')->newFundApplicable(
                    $organization->email,
                    $organization->emailServiceId(),
                    $fund->name,
                    config('forus.front_ends.panel-provider')
                );
            }*/
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

            $providers = $fund->providers()->where([
                'state' => 'approved'
            ])->get();

            $providerCount = $providers->map(function ($fundProvider){
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
                        $referrer['identity'],
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
     * @param string $identity_address
     * @param float|null $amount
     * @param Carbon|null $expire_at
     * @param string|null $note
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function makeVoucher(
        string $identity_address = null,
        float $amount = null,
        Carbon $expire_at = null,
        string $note = null
    ) {
        $amount = $amount ?: self::amountForIdentity($this, $identity_address);
        $expire_at = $expire_at ?: $this->end_date;

        $voucher = $this->vouchers()->create(compact(
            'identity_address', 'amount', 'expire_at', 'note'
        ));

        VoucherCreated::dispatch($voucher);

        return $voucher;
    }
}
