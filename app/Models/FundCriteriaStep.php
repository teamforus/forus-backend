<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\FundCriteriaStep
 *
 * @property int $id
 * @property string $title
 * @property int $fund_id
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $fund
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundCriterion[] $fund_criteria
 * @property-read int|null $fund_criteria_count
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriteriaStep newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriteriaStep newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriteriaStep query()
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriteriaStep whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriteriaStep whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriteriaStep whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriteriaStep whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriteriaStep whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriteriaStep whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundCriteriaStep extends Model
{
    /**
     * @return BelongsTo
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return HasMany
     */
    public function fund_criteria(): HasMany
    {
        return $this->hasMany(FundCriterion::class);
    }
}
