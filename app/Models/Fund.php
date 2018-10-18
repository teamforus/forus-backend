<?php

namespace App\Models;

use App\Services\BunqService\BunqService;
use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Traits\HasMedia;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Class Fund
 * @property mixed $id
 * @property integer $organization_id
 * @property string $state
 * @property string $name
 * @property Organization $organization
 * @property float $budget_total
 * @property float $budget_validated
 * @property float $budget_used
 * @property Media $logo
 * @property Collection $metas
 * @property Collection $products
 * @property Collection $product_categories
 * @property Collection $criteria
 * @property Collection $vouchers
 * @property Collection $voucher_transactions
 * @property Collection $providers
 * @property Collection $validators
 * @property Collection $provider_organizations
 * @property Collection $provider_organizations_approved
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
 */
class Fund extends Model
{
    use HasMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organization_id', 'state', 'name', 'start_date', 'end_date'
    ];

    protected $hidden = [
        'fund_config'
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function provider_organizations() {
        return $this->belongsToMany(
            Organization::class,
            'fund_providers'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function top_ups() {
        return $this->hasMany(FundTopUp::class);
    }

    /**
     * @return float
     */
    public function getBudgetTotalAttribute() {
        return $this->top_ups()->where([
            'state' => 'confirmed'
        ])->sum('amount');
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
    public function getBudgetUsedAttribute() {
        return $this->voucher_transactions->sum('amount');
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
     * @return mixed|null
     */
    private function getFundConfig() {
        try {
            $cfg = collect(json_decode(env('FUNDS_MAPPING')))->where(
                'fund_id', '=', $this->id
            )->first();

            return is_object($cfg) ? $cfg : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return mixed|null
     */
    public function hasFundConfig() {
        try {
            return is_object($this->getFundConfig());
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return mixed|null
     */
    public function getFundKey() {
        try {
            $cfg = $this->getFundConfig();
            return object_get($cfg, 'key');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return mixed|null
     */
    public function getBunqKey() {
        try {
            $cfg = $this->getFundConfig();

            $allowed_ip = collect(
                explode(',', object_get($cfg, 'bunq.allowed_ip', ''))
            )->filter()->toArray();

            return [
                "key" => object_get($cfg, 'bunq.key', false),
                "sandbox" => object_get($cfg, 'bunq.sandbox', false),
                "allowed_ip" => $allowed_ip,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return mixed|null
     */
    public function getFundFormula() {
        try {
            $cfg = $this->getFundConfig();

            return [
                "amount" => object_get($cfg, 'formula.amount', false),
                "multiplier" => object_get($cfg, 'formula.multiplier', false),
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getTrustedRecordOfType(
        Fund $fund,
        string $identity_address,
        string $recordType
    ) {
        $recordRepo = app()->make('forus.services.record');

        $trustedIdentities = $fund->validators->pluck(
            'identity_address'
        );

        /** @var FundCriterion $criterion */
        $recordsOfType = collect($recordRepo->recordsList(
            $identity_address, $recordType
        ));

        $validRecordsOfType = $recordsOfType->map(function($record) use (
            $trustedIdentities
        ) {
            $record['validations'] = collect($record['validations'])->whereIn(
                'identity_address', $trustedIdentities
            )->sortByDesc('created_at');

            return $record;
        })->filter(function($record) {
            return count($record['validations']) > 0;
        })->sortByDesc(function($record) {
            return $record['validations'][0]['created_at'];
        });

        return collect($validRecordsOfType)->first();
    }

    public static function amountForIdentity(Fund $fund, $identityAddress)
    {
        $fundFormula = $fund->getFundFormula();

        $record = self::getTrustedRecordOfType(
            $fund, $identityAddress, $fundFormula['multiplier']
        );

        return $fundFormula['amount'] * $record['value'];
    }

    /**
     * @return BunqService|string
     */
    public function getBunq() {
        $fundBunq = $this->getBunqKey();

        if (empty($fundBunq) || empty($fundBunq['key'])) {
            app('log')->alert('No bunq config for fund: ' . $this->id);
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
            return static::query()->whereIn('id', collect(json_decode(
                env('FUNDS_MAPPING')
            ))->pluck('fund_id')->toArray())->get();
        } catch (\Exception $exception) {
            return collect();
        }
    }
}