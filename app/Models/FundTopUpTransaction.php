<?php

namespace App\Models;

/**
 * App\Models\FundTopUpTransaction
 *
 * @property int $id
 * @property int $fund_top_up_id
 * @property float|null $amount
 * @property string|null $bunq_transaction_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\FundTopUp $fund_top_up
 * @method static \Illuminate\Database\Eloquent\Builder|FundTopUpTransaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundTopUpTransaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundTopUpTransaction query()
 * @method static \Illuminate\Database\Eloquent\Builder|FundTopUpTransaction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundTopUpTransaction whereBunqTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundTopUpTransaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundTopUpTransaction whereFundTopUpId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundTopUpTransaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundTopUpTransaction whereUpdatedAt($value)
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
    function fund_top_up() {
        return $this->belongsTo(FundTopUp::class);
    }
}
