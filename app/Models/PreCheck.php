<?php

namespace App\Models;

use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\FundFormulaProductResource;
use App\Http\Resources\FundResource;
use App\Http\Resources\MediaResource;
use App\Rules\FundRequests\BaseFundRequestRule;
use App\Scopes\Builders\VoucherQuery;
use App\Searches\FundSearch;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;

/**
 * App\Models\PreCheck
 *
 * @property int $id
 * @property int $default
 * @property int $implementation_id
 * @property int|null $order
 * @property string $title
 * @property string $title_short
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Implementation $implementation
 * @property-read Collection|\App\Models\PreCheckRecord[] $pre_check_records
 * @property-read int|null $pre_check_records_count
 * @method static \Illuminate\Database\Eloquent\Builder|PreCheck newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PreCheck newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PreCheck query()
 * @method static \Illuminate\Database\Eloquent\Builder|PreCheck whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PreCheck whereDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PreCheck whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PreCheck whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PreCheck whereImplementationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PreCheck whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PreCheck whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PreCheck whereTitleShort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PreCheck whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PreCheck extends BaseModel
{
    /**
     * @var array
     */
    protected $fillable = [
        'default', 'implementation_id', 'order', 'title', 'title_short', 'description',
    ];

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function implementation(): BelongsTo
    {
        return $this->belongsTo(Implementation::class);
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function pre_check_records(): HasMany
    {
        return $this->hasMany(PreCheckRecord::class);
    }

    /**
     * @param BaseFormRequest $request
     * @return Collection
     */
    public static function getAvailableFunds(BaseFormRequest $request): Collection
    {
        $identity = $request->identity();
        $fundsQuery = Implementation::queryFundsByState('active');

        if ($identity) {
            $fundsQuery->whereDoesntHave('vouchers', fn (
                Builder|Voucher $builder
            ) => VoucherQuery::whereActive($builder->where([
                'identity_address' => $identity->address,
            ])));
        }

        return (new FundSearch(array_merge($request->only([
            'q', 'tag_id', 'organization_id',
        ]), [
            'with_external' => true,
        ]), $fundsQuery))->query()->get();
    }

    /**
     * @param Collection $funds
     * @param array $records
     * @return array
     */
    public static function calculateTotalsPerFund(Collection $funds, array $records): array
    {
        $funds->load([
            'logo.presets',
            'criteria.record_type',
            'fund_config.implementation.pre_checks_records.settings',
        ]);

        return $funds->map(function (Fund $fund) use ($records) {
            $criteria = $fund->criteria
                ->where('optional', false)
                ->where('record_type.pre_check', true)
                ->values();

            $multiplier = $fund->multiplierForIdentity(null, $records);
            $amountIdentity = $fund->amountForIdentity(null, $records);
            $amountIdentityTotal = $multiplier * $amountIdentity;

            $criteria = $criteria->map(function (FundCriterion $criterion) use ($records, $fund) {
                /** @var PreCheckRecordSetting|null $setting */
                /** @var PreCheckRecord|null $preCheckRecord */
                $preCheckRecord = $fund->fund_config
                    ?->implementation
                    ?->pre_checks_records
                    ?->firstWhere('record_type_key', $criterion->record_type_key);

                $setting = $preCheckRecord?->settings?->firstWhere('fund_id', $fund->id);
                $value = $records[$criterion->record_type_key] ?? null;
                $rule = BaseFundRequestRule::validateRecordValue($criterion, $value);

                return [
                    'id' => $criterion->id,
                    'name' => $criterion->record_type->name,
                    'value' => $value,
                    'is_valid' => $criterion->isExcludedByRules($records) || $rule->passes(),
                    'is_knock_out' => $setting?->is_knock_out ?? false,
                    'impact_level' => $setting?->impact_level ?? 100,
                    'knock_out_description' => $setting?->description ?? '',
                    'product_count' => $fund->fund_formula_products()->where(
                        'record_type_key_multiplier', $criterion->record_type->key
                    )->count(),
                ];
            });

            return [
                ...$fund->only([
                    'id', 'name', 'description', 'description_short',
                    'external_link_text', 'external_link_url', 'is_external',
                ]),
                'logo' => new MediaResource($fund->logo),
                'parent' => $fund->parent ? new FundResource($fund->parent) : null,
                'children' => $fund->children ? FundResource::collection($fund->children) : [],
                'criteria' => $criteria,
                'is_valid' => $criteria->every(fn($criterion) => $criterion['is_valid']),
                'identity_multiplier' => $multiplier,
                'allow_direct_requests' => $fund->fund_config?->allow_direct_requests ?? false,
                'amount_total' => currency_format($amountIdentityTotal),
                'amount_total_locale' => currency_format_locale($amountIdentityTotal),
                'amount_for_identity' => currency_format($amountIdentity),
                'amount_for_identity_locale' => currency_format_locale($amountIdentity),
                'fund_formula_products' => self::getFundFormulaProducts($fund, $records),
                'product_count' => $fund->fund_formula_products()->count(),
                'products_amount_total' => array_sum(array_pluck($fund->fund_formula_products, 'price')),
            ];
        })->toArray();
    }

    /**
     * @param Fund $fund
     * @param array|null $records
     * @return array
     */
    public static function getFundFormulaProducts(Fund $fund, array $records = null): array
    {
        $products = $fund->fund_formula_products->sortByDesc('product_id')->map(fn ($formula) => [
            'record' => $formula->record_type ? $formula->record_type->name : 'Product tegoed',
            'type' => $formula->record_type_key_multiplier ? 'Multiply' : 'Vastgesteld',
            'name' => $formula->product->name,
            'count' => $formula->record_type_key_multiplier ? Arr::get($records, $formula->record_type_key_multiplier) : 1,
        ]);

        $formula = $fund->fund_formulas->map(fn ($formula) => [
            'record' => $formula->record_type ? $formula->record_type->name : 'Vastbedrag',
            'type' => $formula->type_locale,
            'value' => $formula->amount_locale,
            'count' => $formula->record_type_key ? Arr::get($records, $formula->record_type_key) : 1,
            'total' => currency_format_locale($formula->amount),
            'amount' => currency_format($formula->amount),
        ]);

        return [
            'total_amount' => currency_format_locale($formula->sum('amount')),
            'products' => $products->toArray(),
            'items' => $formula,
        ];
    }
}
