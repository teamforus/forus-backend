<?php

namespace App\Models;

/**
 * App\Models\VoucherTransactionNote
 *
 * @property int $id
 * @property int $voucher_transaction_id
 * @property string $icon
 * @property string $message
 * @property int $pin_to_top
 * @property string $group
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherTransactionNote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherTransactionNote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherTransactionNote query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherTransactionNote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherTransactionNote whereGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherTransactionNote whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherTransactionNote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherTransactionNote whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherTransactionNote wherePinToTop($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherTransactionNote whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherTransactionNote whereVoucherTransactionId($value)
 * @mixin \Eloquent
 */
class VoucherTransactionNote extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'voucher_transaction_id', 'icon', 'message', 'pin_to_top', 'group'
    ];
}
