<?php

namespace App\Models;

/**
 * App\Models\FundCriterion
 *
 * @property int $id
 * @property int $fund_id
 * @property string $record_type_key
 * @property string $operator
 * @property string $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $fund
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion whereOperator($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion whereRecordTypeKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundCriterion whereValue($value)
 * @mixin \Eloquent
 */
class FundCriterion extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fund_id', 'record_type_key', 'operator', 'value'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function fund() {
        return $this->belongsTo(Fund::class);
    }
}
