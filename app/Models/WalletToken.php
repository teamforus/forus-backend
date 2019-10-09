<?php

namespace App\Models;

/**
 * App\Models\WalletToken
 *
 * @property int $id
 * @property int $wallet_id
 * @property int $token_id
 * @property int $amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @property-read \App\Models\Token $token
 * @property-read \App\Models\Wallet $wallet
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletToken newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletToken newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletToken query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletToken whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletToken whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletToken whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletToken whereTokenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletToken whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletToken whereWalletId($value)
 * @mixin \Eloquent
 */
class WalletToken extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'wallet_id', 'token_id', 'amount'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function wallet() {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function token() {
        return $this->belongsTo(Token::class);
    }
}
