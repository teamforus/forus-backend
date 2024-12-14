<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\VoucherToken
 *
 * @property int $id
 * @property int $voucher_id
 * @property string $address
 * @property int $need_confirmation
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Voucher $voucher
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherToken newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherToken newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherToken query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherToken whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherToken whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherToken whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherToken whereNeedConfirmation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherToken whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherToken whereVoucherId($value)
 * @mixin \Eloquent
 */
class VoucherToken extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'voucher_id', 'address', 'need_confirmation'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function voucher(): BelongsTo {
        return $this->belongsTo(Voucher::class);
    }
}
