<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class FundFormula
 * @property mixed $id
 * @property mixed $fund_id
 * @property string $type
 * @property string $record_type_key
 * @property float $amount
 * @property Fund $fund
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
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
