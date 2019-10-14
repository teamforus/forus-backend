<?php

namespace App\Models;

/**
 * App\Models\WalletAsset
 *
 * @property int $id
 * @property int $wallet_id
 * @property string|null $address
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @property-read \App\Models\Wallet $wallet
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletAsset newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletAsset newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletAsset query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletAsset whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletAsset whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletAsset whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletAsset whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletAsset whereWalletId($value)
 * @mixin \Eloquent
 */
class WalletAsset extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'wallet_id', 'address'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function wallet() {
        return $this->belongsTo(Wallet::class);
    }
}
