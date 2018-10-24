<?php

namespace App\Models;

use Carbon\Carbon;

/**
 * Class VoucherTransactionNote
 * @property mixed $id
 * @property mixed $voucher_transaction_id
 * @property string $icon
 * @property string $message
 * @property boolean $pin_to_top
 * @property string $group
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
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
