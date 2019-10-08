<?php

namespace App\Models;

/**
 * App\Models\TransactionRequest
 *
 * @property int $id
 * @property int $token_id
 * @property int $from_wallet_id
 * @property int $to_wallet_id
 * @property int $transaction_id
 * @property int $amount
 * @property string $type
 * @property string $state
 * @property string $token_string
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Wallet $from_wallet
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @property-read \App\Models\Wallet $to_wallet
 * @property-read \App\Models\Token $token
 * @property-read \App\Models\Transaction $transaction
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TransactionRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TransactionRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TransactionRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TransactionRequest whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TransactionRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TransactionRequest whereFromWalletId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TransactionRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TransactionRequest whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TransactionRequest whereToWalletId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TransactionRequest whereTokenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TransactionRequest whereTokenString($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TransactionRequest whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TransactionRequest whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TransactionRequest whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class TransactionRequest extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'token_id', 'from_wallet_id', 'to_wallet_id', 'amount', 'state',
        'type', 'transaction_id', 'token_string'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function token() {
        return $this->belongsTo(Token::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function from_wallet() {
        return $this->belongsTo(Wallet::class, 'from_wallet_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function to_wallet() {
        return $this->belongsTo(Wallet::class, 'to_wallet_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transaction() {
        return $this->belongsTo(Transaction::class);
    }
}
