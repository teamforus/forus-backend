<?php

namespace App\Models;

/**
 * App\Models\WalletVoucher
 *
 * @property int $id
 * @property int $wallet_id
 * @property int $token_id
 * @property int|null $product_id
 * @property int $amount
 * @property string $type
 * @property string $state
 * @property string|null $address
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @property-read \App\Models\Product|null $product
 * @property-read \App\Models\Token $token
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\WalletVoucherToken[] $voucher_tokens
 * @property-read int|null $voucher_tokens_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\WalletVoucherTransaction[] $voucher_transactions
 * @property-read int|null $voucher_transactions_count
 * @property-read \App\Models\Wallet $wallet
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucher newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucher newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucher query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucher whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucher whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucher whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucher whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucher whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucher whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucher whereTokenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucher whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucher whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucher whereWalletId($value)
 * @mixin \Eloquent
 */
class WalletVoucher extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'wallet_id', 'token_id', 'product_id', 'amount', 'type', 'state',
        'address'
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product() {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function voucher_tokens() {
        return $this->hasMany(WalletVoucherToken::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function voucher_transactions() {
        return $this->hasMany(WalletVoucherTransaction::class);
    }
}
