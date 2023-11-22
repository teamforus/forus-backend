<?php

namespace App\Models;

use App\Rules\FundRequests\BaseFundRequestRule;
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
     * @param Identity $identity
     * @param array $preChecksData
     * @return array
     */
    public static function calculateTotals(
        Implementation $implementation,
        Identity $identity,
        array $preChecksData
    ): array {
        return $implementation->funds->reduce(function (array $result, Fund $fund) use ($identity, $preChecksData) {
            $invalidCriteria = $fund->criteria
                ->filter(function (FundCriterion $criterion) use ($preChecksData){
                    $preCheck = collect($preChecksData)->first(function ($preCheck) {
                        return collect($preCheck['pre_check_records'])->first(function ($record, $criterion) {
                            return $record['record_type'] == $criterion->record_type;
                        });
                    });
                    $value = $preCheck['input_value'] ?? null;

                    BaseFundRequestRule::validateRecordValue($criterion, $value)->passes();
                });

            return array_merge($result, [
                'fund_id' => $fund->id,
                'fund_criteria' => $fund->criteria,
                'fund_criteria_valid_count' => $fund->criteria_count - $invalidCriteria->count(),
                'fund_criteria_invalid_count' => $invalidCriteria->count(),
            ]);
        }, []);
    }
}
