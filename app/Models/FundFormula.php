<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\FundFormula
 *
 * @property int $id
 * @property int $fund_id
 * @property string $type
 * @property float $amount
 * @property string|null $record_type_key
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $fund
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundFormula newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundFormula newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundFormula query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundFormula whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundFormula whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundFormula whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundFormula whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundFormula whereRecordTypeKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundFormula whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundFormula whereUpdatedAt($value)
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
    public function fund() {
        return $this->belongsTo(Fund::class);
    }
}
