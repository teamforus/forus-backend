<?php

namespace App\Models;

use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Traits\HasMedia;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Product
 * @property mixed $id
 * @property string $name
 * @property string $description
 * @property integer $organization_id
 * @property integer $product_category_id
 * @property integer $price
 * @property integer $old_price
 * @property integer $total_amount
 * @property bool $is_offer
 * @property bool $sold_out
 * @property bool $expired
 * @property bool $service
 * @property Organization $organization
 * @property ProductCategory $product_category
 * @property Media $photo
 * @property Collection $funds
 * @property Carbon $expire_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
 */
class Product extends Model
{
    use HasMedia, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'description', 'organization_id', 'product_category_id',
        'price', 'old_price', 'total_amount', 'expire_at', 'sold_out'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    public $dates = [
        'expire_at', 'deleted_at'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization() {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function vouchers() {
        return $this->hasMany(Voucher::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function voucher_transactions() {
        return $this->hasMany(VoucherTransaction::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product_category() {
        return $this->belongsTo(ProductCategory::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function funds() {
        return $this->hasManyThrough(
            Fund::class,
            FundProduct::class
        );
    }

    /**
     * Get fund logo
     * @return MorphOne
     */
    public function photo() {
        return $this->morphOne(Media::class, 'mediable')->where([
            'type' => 'product_photo'
        ]);
    }

    /**
     * The product is offer
     *
     * @return bool
     */
    public function getIsOfferAttribute() {
        return !!$this->old_price;
    }

    /**
     * The product is sold out
     *
     * @param $value
     * @return bool
     */
    public function getSoldOutAttribute($value) {
        return !!$value;
    }

    /**
     * The product is expired
     *
     * @return bool
     */
    public function getExpiredAttribute() {
        return !!$this->expire_at->isPast();
    }

    /**
     * Count vouchers generated for this product but not used
     *
     * @return int
     */
    public function countReserved() {
        return $this->vouchers()->doesntHave('transactions')->count();
    }

    /**
     * Count actually sold products
     *
     * @return int
     */
    public function countSold() {
        return $this->voucher_transactions()->count();
    }

    /**
     * Update sold out state for the product
     */
    public function updateSoldOutState() {
        $totalProducts = $this->countReserved() + $this->countSold();

        $this->update([
            'sold_out' => $totalProducts >= $this->total_amount
        ]);
    }
}
