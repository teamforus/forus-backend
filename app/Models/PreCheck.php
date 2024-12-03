<?php

namespace App\Models;

use App\Http\Resources\FundResource;
use App\Http\Resources\MediaResource;
use App\Rules\FundRequests\BaseFundRequestRule;
use App\Scopes\Builders\VoucherQuery;
use App\Searches\FundSearch;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     * @param Identity|null $identity
     * @param array $filters
     * @return Collection
     */
    public static function getAvailableFunds(
        Identity $identity = null,
        array $filters = [],
    ): Collection {
        $fundsQuery = Implementation::queryFundsByState('active');

        $fundsQuery->whereDoesntHave('fund_config', function (Builder $builder) {
            $builder->where('pre_check_excluded', true);
        });

        if ($identity) {
            $fundsQuery->whereDoesntHave('vouchers', fn (
                Builder|Voucher $builder
            ) => VoucherQuery::whereActive($builder->where([
                'identity_address' => $identity->address,
            ])));
        }

        return (new FundSearch([
            ...$filters,
            'with_external' => true,
        ], $fundsQuery))->query()->get();
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
            $baseFields = [
                ...$fund->only([
                    'id', 'name', 'description', 'description_short',
                    'external_link_text', 'external_link_url', 'is_external',
                ]),
                'logo' => new MediaResource($fund->logo),
                'parent' => $fund->parent ? new FundResource($fund->parent) : null,
                'children' => $fund->children ? FundResource::collection($fund->children) : [],
            ];

            if ($fund->fund_config->pre_check_note) {
                return [
                    ...$baseFields,
                    'pre_check_note' => $fund->fund_config->pre_check_note,
                    'is_valid' => false,
                    'criteria' => [],
                ];
            }

            $criteria = static::buildPreCheckCriteriaList($fund, $records);
            $multiplier = $fund->multiplierForIdentity(null, $records);
            $amountIdentity = $fund->amountForIdentity(null, $records);
            $amountIdentityTotal = $multiplier * $amountIdentity;

            return [
                ...$baseFields,
                'criteria' => $criteria,
                'is_valid' => $criteria->every(fn($criterion) => $criterion['is_valid']),
                'identity_multiplier' => $multiplier,
                'allow_direct_requests' => $fund->fund_config?->allow_direct_requests ?? false,
                'amount_total' => currency_format($amountIdentityTotal),
                'amount_total_locale' => currency_format_locale($amountIdentityTotal),
                'amount_for_identity' => currency_format($amountIdentity),
                'amount_for_identity_locale' => currency_format_locale($amountIdentity),
            ];
        })->toArray();
    }

    protected static function buildPreCheckCriteriaList(Fund $fund, array $records)
    {
        $criteria = $fund->criteria
            ->where('optional', false)
            ->where('record_type.pre_check', true)
            ->values();

        return $criteria->map(function (FundCriterion $criterion) use ($records, $fund) {
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
            ];
        });
    }
}
