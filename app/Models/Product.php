<?php

namespace App\Models;

use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Traits\HasMedia;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Symfony\Component\Debug\Tests\Fixtures\LoggerThatSetAnErrorHandler;

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
 * @property integer $stock_amount
 * @property bool $is_offer
 * @property bool $sold_out
 * @property bool $expired
 * @property bool $service
 * @property Collection $vouchers_reserved
 * @property Collection $voucher_transactions
 * @property Organization $organization
 * @property ProductCategory $product_category
 * @property Media $photo
 * @property Collection $funds
 * @property Carbon $expire_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
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
    public function vouchers_reserved() {
        return $this->hasMany(Voucher::class)->whereDoesntHave('transactions');
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
        return $this->expire_at->isPast();
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
     * @return int
     */
    public function getStockAmountAttribute() {
        return $this->total_amount - (
            $this->vouchers_reserved->count() +
            $this->voucher_transactions->count());
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

    /**
     * @param $currency
     * @return float|int
     */
    public function getPriceByCurrency($currency)
    {
        if ($currency == Fund::CURRENCY_ETHER) {
            return round($this->price * env('ETHEREUM_EUR_CURRENCY_RATE', 0.006), 5);
        }

        return $this->price;
    }
    /**
     * @param $currency
     * @return float|int
     */
    public function getOldPriceByCurrency($currency)
    {
        if ($currency == Fund::CURRENCY_ETHER) {
            return round($this->old_price * env('ETHEREUM_EUR_CURRENCY_RATE', 0.006), 5);
        }

        return $this->old_price;
    }
     /**
     * @return Builder
     */
    public static function searchQuery() {
        $funds = Implementation::activeFunds()->pluck('id');
        $organizationIds = FundProvider::whereIn('fund_id', $funds)->where([
            'state' => 'approved'
        ])->pluck('organization_id');

        return Product::query()->whereIn(
            'organization_id', $organizationIds
        )->where('sold_out', false)->where(
            'expire_at', '>', date('Y-m-d')
        )->orderBy('created_at', 'desc');
    }

    /**
     * @param Request $request
     * @return Builder
     */
    public static function search(
        Request $request
    ) {
        $query = self::searchQuery();

        if ($request->has('product_category_id')) {
            $productCategories = ProductCategory::descendantsAndSelf(
                $request->input('product_category_id')
            )->pluck('id');

            $query->whereIn('product_category_id', $productCategories);
        }

        if (!$request->has('q')) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($request) {
            return $query
                ->where('name', 'LIKE', "%{$request->input('q')}%")
                ->orWhere('description', 'LIKE', "%{$request->input('q')}%");
        });
    }
}
