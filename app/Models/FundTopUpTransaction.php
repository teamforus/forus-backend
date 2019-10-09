<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\FundTopUpTransaction
 *
 * @property int $id
 * @property int $fund_top_up_id
 * @property float|null $amount
 * @property string|null $bunq_transaction_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundTopUpTransaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundTopUpTransaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundTopUpTransaction query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundTopUpTransaction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundTopUpTransaction whereBunqTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundTopUpTransaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundTopUpTransaction whereFundTopUpId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundTopUpTransaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundTopUpTransaction whereUpdatedAt($value)
 * @mixin \Eloquent
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
