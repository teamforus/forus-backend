<?php

namespace App\Models;

/**
 * App\Models\WalletVoucherTransaction
 *
 * @property int $id
 * @property int $token_id
 * @property int $fund_provider_id
 * @property int $wallet_voucher_id
 * @property int $amount
 * @property string $type
 * @property string $state
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @property-read \App\Models\FundProvider $provider
 * @property-read \App\Models\Token $token
 * @property-read \App\Models\WalletVoucher $wallet_voucher
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucherTransaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucherTransaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucherTransaction query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucherTransaction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucherTransaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucherTransaction whereFundProviderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucherTransaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucherTransaction whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucherTransaction whereTokenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucherTransaction whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucherTransaction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucherTransaction whereWalletVoucherId($value)
 * @mixin \Eloquent
 */
class WalletVoucherTransaction extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'token_id', 'provider_id', 'wallet_voucher_id', 'amount', 'type',
        'state'
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
    public function provider() {
        return $this->belongsTo(FundProvider::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function wallet_voucher() {
        return $this->belongsTo(WalletVoucher::class);
    }
}
