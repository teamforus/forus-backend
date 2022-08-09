<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\FundLimitMultiplier
 *
 * @property int $id
 * @property int $fund_id
 * @property string|null $record_type_key
 * @property int $multiplier
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $fund
 * @method static \Illuminate\Database\Eloquent\Builder|FundLimitMultiplier newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundLimitMultiplier newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundLimitMultiplier query()
 * @method static \Illuminate\Database\Eloquent\Builder|FundLimitMultiplier whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundLimitMultiplier whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundLimitMultiplier whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundLimitMultiplier whereMultiplier($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundLimitMultiplier whereRecordTypeKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundLimitMultiplier whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundLimitMultiplier extends BaseModel
{
    protected $fillable = [
        'id', 'fund_id', 'multiplier', 'record_type_key'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fund(): BelongsTo {
        return $this->belongsTo(Fund::class);
    }
}
