<?php

namespace App\Models;

use App\Http\Resources\FundCriterionResource;
use App\Http\Resources\FundResource;
use App\Rules\FundRequests\BaseFundRequestRule;
use App\Scopes\Builders\VoucherQuery;
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
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Implementation $implementation
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PreCheckRecord[] $pre_check_records
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
 * @method static \Illuminate\Database\Eloquent\Builder|PreCheck whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PreCheck extends BaseModel
{
    /**
     * @var array
     */
    protected $fillable = [
        'default', 'implementation_id', 'order',
        'title', 'description'
    ];

    /**
     * @return BelongsTo
     */
    public function implementation(): BelongsTo
    {
        return $this->belongsTo(Implementation::class);
    }

    /**
     * @return HasMany
     */
    public function pre_check_records(): HasMany
    {
        return $this->hasMany(PreCheckRecord::class);
    }

    /**
     * @param Implementation $implementation
     * @param array $data
     * @param Identity|null $identity
     * @return array
     */
    public static function calculateTotalsPerFund(
        Implementation $implementation,
        array $data,
        Identity $identity = null,
    ): array {
        $preChecksData = $data['pre_checks'];

//        $fundsWithVouchers = $identity ? VoucherQuery::whereActive(
//            $identity->vouchers()
//        )->pluck('fund_id') : [];

        $fundsWithVouchers = [];
        $availableFunds = $implementation->funds()->whereNotIn(
            'funds.id', $fundsWithVouchers
        )->get();

        return $availableFunds->reduce(function (array $result, Fund $fund) use ($identity, $preChecksData) {
            $invalidCriteria = $fund->criteria->filter(function (FundCriterion $criterion) use ($preChecksData, &$criteriaMap) {
                $recordValue = null;

                collect($preChecksData)->each(function ($preCheck) use ($criterion, &$recordValue) {
                    $preCheckRecord = collect($preCheck['pre_check_records'])->filter(function ($record) use ($criterion) {
                        return $record['record_type']['key'] == $criterion->record_type_key;
                    })->first();

                    if ($preCheckRecord) {
                        $recordValue = $preCheckRecord['input_value'] ?? null;
                    }
                });

                $criteriaMap[] = self::mapCriteria($criterion, $recordValue);

                return !BaseFundRequestRule::validateRecordValue($criterion, $recordValue)->passes();
            });

            $validCriteriaCount = $fund->criteria->count() - $invalidCriteria->count();
            $validCriteriaPercentage = round(($validCriteriaCount / $fund->criteria->count()) * 100);

            $result[] = array_merge([
                'id' => $fund->id,
                'criteria' => $criteriaMap,
                'criteria_valid_percentage' => $validCriteriaPercentage,
                'criteria_invalid_percentage' => 100 - $validCriteriaPercentage,
                'parent' => new FundResource($fund),
                'children' => FundResource::collection($fund->children),
                'amount_for_identity' => currency_format($fund->amountForIdentity($identity)),
                'multiplier_for_identity' => $fund->multiplierForIdentity($identity),
                'amount_total' => $fund->multiplierForIdentity($identity) * $fund->amountForIdentity($identity),
                'amount_total_currency' => currency_format(
                    $fund->multiplierForIdentity($identity) * $fund->amountForIdentity($identity)
                ),
            ], $fund->only('name', 'description', 'description_short'));

            return $result;
        }, []);
    }

    /**
     * @param FundCriterion $criterion
     * @param $recordValue
     * @return array
     */
    private static function mapCriteria(FundCriterion $criterion, $recordValue): array
    {
        return [
            'id' => $criterion->id,
            'value' => $recordValue,
            'record_type_name' => $criterion->record_type->name,
            'is_valid' => BaseFundRequestRule::validateRecordValue($criterion, $recordValue)->passes(),
        ];
    }
}
