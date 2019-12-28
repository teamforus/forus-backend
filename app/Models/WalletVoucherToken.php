<?php

namespace App\Models;

/**
 * App\Models\WalletVoucherToken
 *
 * @property int $id
 * @property int $wallet_voucher_id
 * @property string $type
 * @property string $token
 * @property int $expires_in
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @property-read \App\Models\WalletVoucher $wallet_voucher
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucherToken newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucherToken newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucherToken query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucherToken whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucherToken whereExpiresIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucherToken whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucherToken whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucherToken whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucherToken whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletVoucherToken whereWalletVoucherId($value)
 * @mixin \Eloquent
 */
class WalletVoucherToken extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'wallet_voucher_id', 'type', 'token', 'expires_in'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function wallet_voucher() {
        return $this->belongsTo(WalletVoucher::class);
    }
}
