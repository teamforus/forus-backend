<?php

namespace App\Models;

use Carbon\Carbon;

/**
 * Class FundCriterion
 * @property mixed $id
 * @property integer $fund_id
 * @property string $record_type_key
 * @property string $operator
 * @property string $value
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Fund $fund
 * @package App\Models
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
