<?php

namespace App\Models;

/**
 * App\Models\VoucherTransactionNote.
 *
 * @property int $id
 * @property int $voucher_transaction_id
 * @property string $icon
 * @property string $message
 * @property int $pin_to_top
 * @property string $group
 * @property bool $shared
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherTransactionNote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherTransactionNote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherTransactionNote query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherTransactionNote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherTransactionNote whereGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherTransactionNote whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherTransactionNote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherTransactionNote whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherTransactionNote wherePinToTop($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherTransactionNote whereShared($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherTransactionNote whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherTransactionNote whereVoucherTransactionId($value)
 * @mixin \Eloquent
 */
class VoucherTransactionNote extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'voucher_transaction_id', 'icon', 'message', 'pin_to_top', 'group', 'shared',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'shared' => 'boolean',
    ];
}
