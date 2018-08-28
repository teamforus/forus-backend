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
        'voucher_id', 'organization_id', 'product_id', 'address', 'amount'
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
}
