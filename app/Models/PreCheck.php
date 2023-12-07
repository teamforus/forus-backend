<?php

namespace App\Models;

use App\Http\Resources\FundResource;
use App\Rules\FundRequests\BaseFundRequestRule;
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
     * @param Collection $funds
     * @param array $records
     * @return array
     */
    public static function calculateTotalsPerFund(Collection $funds, array $records): array
    {
        return $funds->map(function (array $result, Fund $fund) use ($records) {
            $value = $records[$criterion->record_type_key] ?? '';

            $criteria = $fund->criteria->map(fn (FundCriterion $criterion) => [
                'id' => $criterion->id,
                'value' => $value,
                'name' => $criterion->record_type->name,
                'is_valid' => BaseFundRequestRule::validateRecordValue($criterion, $value)->passes(),
            ]);

            return array_merge([
                ...$fund->only(['id', 'name', 'description', 'description_short']),
                'parent' => $fund->parent ? new FundResource($fund->parent) : null,
                'criteria' => $criteria,
                'children' => $fund->children ? FundResource::collection($fund->children) : [],
                /*'amount_for_identity' => currency_format($fund->amountForIdentity(null, $value)),
                'multiplier_for_identity' => $fund->multiplierForIdentity($identity),
                'amount_total' => $fund->multiplierForIdentity($identity) * $fund->amountForIdentity($identity),
                'amount_total_currency' => currency_format(
                    $fund->multiplierForIdentity($identity) * $fund->amountForIdentity($identity)
                ),*/
            ]);
        })->toArray();
    }
}
