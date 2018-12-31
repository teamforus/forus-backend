<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class FundTopUpTransaction
 * @property mixed $id
 * @property mixed $fund_top_up_id
 * @property float $amount
 * @property mixed $bunq_transaction_id
 * @property FundTopUp $fund_top_up
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
 */
class FundTopUpTransaction extends Model
{
    protected $fillable = [
        'fund_top_up_id', 'bunq_transaction_id', 'amount'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    protected function fund_top_up() {
        return $this->belongsTo(FundTopUp::class);
    }
}
