<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\FundFormula
 *
 * @property int $id
 * @property int $fund_id
 * @property string $type
 * @property string $amount
 * @property string|null $record_type_key
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $fund
 * @method static \Illuminate\Database\Eloquent\Builder|FundFormula newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundFormula newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundFormula query()
 * @method static \Illuminate\Database\Eloquent\Builder|FundFormula whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundFormula whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundFormula whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundFormula whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundFormula whereRecordTypeKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundFormula whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundFormula whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundFormula extends Model
{
    protected $fillable = [
        'id', 'fund_id', 'type', 'amount', 'record_type_key'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fund(): BelongsTo {
        return $this->belongsTo(Fund::class);
    }
}
