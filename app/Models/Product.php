<?php

namespace App\Models;

use App\Events\Products\ProductSoldOut;
use App\Scopes\Builders\ProductQuery;
use App\Services\EventLogService\Traits\HasLogs;
use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Traits\HasMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;

/**
 * App\Models\Product
 *
 * @property int $id
 * @property int $organization_id
 * @property int $product_category_id
 * @property string $name
 * @property string $description
 * @property float $price
 * @property float|null $old_price
 * @property int $total_amount
 * @property bool $unlimited_stock
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon $expire_at
 * @property bool $sold_out
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundProviderChat[] $fund_provider_chats
 * @property-read int|null $fund_provider_chats_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundProvider[] $fund_providers
 * @property-read int|null $fund_providers_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Fund[] $funds
 * @property-read int|null $funds_count
 * @property-read bool $expired
 * @property-read bool $is_offer
 * @property-read int $stock_amount
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\MediaService\Models\Media[] $medias
 * @property-read int|null $medias_count
 * @property-read \App\Models\Organization $organization
 * @property-read \App\Services\MediaService\Models\Media $photo
 * @property-read \App\Models\ProductCategory $product_category
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\VoucherTransaction[] $voucher_transactions
 * @property-read int|null $voucher_transactions_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Voucher[] $vouchers
 * @property-read int|null $vouchers_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Voucher[] $vouchers_reserved
 * @property-read int|null $vouchers_reserved_count
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Product onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereExpireAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereOldPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereProductCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereSoldOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereUnlimitedStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Product withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Product withoutTrashed()
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\EventLogService\Models\EventLog[] $logs
 * @property-read int|null $logs_count
 */
class Product extends Model
{
    use HasMedia, SoftDeletes, HasLogs;

    const EVENT_CREATED = 'created';
    const EVENT_SOLD_OUT = 'sold_out';
    const EVENT_EXPIRED = 'expired';
    const EVENT_RESERVED = 'reserved';

    const EVENT_APPROVED = 'approved';
    const EVENT_REVOKED = 'revoked';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'description', 'organization_id', 'product_category_id',
        'price', 'old_price', 'total_amount', 'expire_at', 'sold_out',
        'unlimited_stock'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    public $dates = [
        'expire_at', 'deleted_at'
    ];

    protected $casts = [
        'unlimited_stock' => 'boolean'
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function fund_providers() {
        return $this->belongsToMany(
            FundProvider::class,
            'fund_provider_products'
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fund_provider_chats() {
        return $this->hasMany(FundProviderChat::class);
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
        if (!$this->unlimited_stock) {
            $totalProducts = $this->countReserved() + $this->countSold();
            $sold_out = $totalProducts >= $this->total_amount;
            $this->update(compact('sold_out'));

            if ($sold_out) {
                broadcast(new ProductSoldOut($this));
            }
        }
    }

    /**
     * @return Builder
     */
    public static function searchQuery() {
        $query = Product::query();
        $activeFunds = Implementation::activeFunds()->pluck('id')->toArray();

        // only in stock and not expired
        $query = ProductQuery::inStockAndActiveFilter($query);

        // only approved by at least one sponsor
        $query = ProductQuery::approvedForFundsFilter($query, $activeFunds);

        return $query;
    }

    /**
     * @param Request $request
     * @return Builder
     */
    public static function search(Request $request) {
        $query = self::searchQuery()->orderBy('created_at', 'desc');

        // filter by product_category_id
        if ($category_id = $request->input('product_category_id')) {
            $query = ProductQuery::productCategoriesFilter($query, $category_id);
        }

        // filter by fund_id
        if ($request->has('fund_id') && $fund_id = $request->input('fund_id')) {
            $query = ProductQuery::approvedForFundsFilter($query, $fund_id);
        }

        // filter by unlimited stock
        if ($request->has('unlimited_stock') &&
            $unlimited_stock = filter_bool($request->input('unlimited_stock'))) {
            return ProductQuery::unlimitedStockFilter($query, $unlimited_stock);
        }

        // filter by string query
        if ($request->has('q') && !empty($q = $request->input('q'))) {
            return ProductQuery::queryDeepFilter($query, $q);
        }

        return $query;
    }

    /**
     * @param Request $request
     * @return Builder
     */
    public static function searchAny(Request $request) {
        $query = self::query()->orderBy('created_at', 'desc');

        // filter by unlimited stock
        if ($request->has('unlimited_stock') &&
            $unlimited_stock = filter_bool($request->input('unlimited_stock'))) {
            return ProductQuery::unlimitedStockFilter($query, $unlimited_stock);
        }

        // filter by string query
        if ($request->has('q') && !empty($q = $request->input('q'))) {
            return ProductQuery::queryFilter($query, $q);
        }

        return $query;
    }

    /**
     * Send product sold out email to provider
     * @return void
     */
    public function sendSoldOutEmail(): void
    {
        $mailService = resolve('forus.services.notification');
        $mailService->productSoldOut(
            $this->organization->email,
            Implementation::emailFrom(),
            $this->name,
            Implementation::active()['url_provider']
        );
    }

    /**
     * Send product reserved email to provider
     * @param Voucher $voucher
     * @return void
     */
    public function sendProductReservedEmail(Voucher $voucher): void
    {
        $mailService = resolve('forus.services.notification');
        $mailService->productReserved(
            $this->organization->email,
            Implementation::emailFrom(),
            $this->name,
            format_date_locale($voucher->expire_at)
        );
    }

    /**
     * Send product reserved email to user
     * @param Voucher $voucher
     * * @return void
     */
    public function sendProductReservedUserEmail(Voucher $voucher): void
    {
        if (!$voucher->identity_address) {
            return;
        }

        $mailService = resolve('forus.services.notification');
        $recordService = resolve('forus.services.record');

        $mailService->productReservedUser(
            $recordService->primaryEmailByAddress($voucher->identity_address),
            $voucher->fund->fund_config->implementation->getEmailFrom(),
            $this->name,
            $this->price,
            $this->organization->phone,
            $this->organization->email,
            $voucher->token_without_confirmation->address,
            $this->organization->name,
            format_date_locale($voucher->expire_at->subDay())
        );
    }
}
