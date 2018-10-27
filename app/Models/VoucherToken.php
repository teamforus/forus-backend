<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class VoucherToken
 * @property mixed $id
 * @property mixed $voucher_id
 * @property string $address
 * @property boolean $need_confirmation
 * @property Voucher $voucher
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
 */
class VoucherToken extends Model
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
    public function voucher() {
        return $this->belongsTo(Voucher::class);
    }
}
