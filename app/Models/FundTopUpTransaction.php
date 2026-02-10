<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\FundTopUpTransaction.
 *
 * @property int $id
 * @property int $fund_top_up_id
 * @property float|null $amount
 * @property string|null $bank_transaction_id
 * @property int|null $bank_connection_account_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\BankConnectionAccount|null $bank_connection_account
 * @property-read \App\Models\FundTopUp $fund_top_up
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundTopUpTransaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundTopUpTransaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundTopUpTransaction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundTopUpTransaction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundTopUpTransaction whereBankConnectionAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundTopUpTransaction whereBankTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundTopUpTransaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundTopUpTransaction whereFundTopUpId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundTopUpTransaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundTopUpTransaction whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundTopUpTransaction extends Model
{
    protected $perPage = 10;

    protected $fillable = [
        'fund_top_up_id', 'bank_transaction_id', 'amount',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function fund_top_up(): BelongsTo
    {
        return $this->belongsTo(FundTopUp::class);
    }

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function bank_connection_account(): BelongsTo
    {
        return $this->belongsTo(BankConnectionAccount::class);
    }
}
