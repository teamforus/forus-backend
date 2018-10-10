<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class VoucherTransaction
 * @property mixed $id
 * @property integer $voucher_id
 * @property integer $organization_id
 * @property integer $product_id
 * @property string $address
 * @property float $amount
 * @property Fund $fund
 * @property integer $attempts
 * @property integer $payment_id
 * @property string $state
 * @property Product $product
 * @property Organization $organization
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
 */
class VoucherTransaction extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'voucher_id', 'organization_id', 'product_id', 'address', 'amount',
        'state', 'payment_id', 'attempts', 'last_attempt_at'
    ];

    protected $hidden = [
        'voucher_id', 'last_attempt_at', 'attempts'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product() {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization() {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return mixed
     */
    public function getTransactionDetailsAttribute()
    {
        return collect(app()->make('bunq')->paymentDetails(
            $this->payment_id
        ));
    }
}
