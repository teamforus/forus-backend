<?php

namespace App\Models;

use App\Events\Products\ProductSoldOut;
use App\Http\Requests\BaseFormRequest;
use App\Models\Traits\HasBookmarks;
use App\Notifications\Organizations\Funds\FundProductSubsidyRemovedNotification;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\OfficeQuery;
use App\Scopes\Builders\ProductQuery;
use App\Scopes\Builders\TrashedQuery;
use App\Services\EventLogService\Traits\HasLogs;
use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Traits\HasMedia;
use App\Traits\HasMarkdownDescription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * App\Models\Product
 *
 * @property int $id
 * @property int $organization_id
 * @property int $product_category_id
 * @property string $name
 * @property string $description
 * @property string|null $description_text
 * @property string|null $alternative_text
 * @property string $price
 * @property int $total_amount
 * @property bool $unlimited_stock
 * @property string $price_type
 * @property string|null $price_discount
 * @property int $show_on_webshop
 * @property bool $reservation_enabled
 * @property string $reservation_policy
 * @property bool $reservation_fields
 * @property string $reservation_phone
 * @property string $reservation_address
 * @property string $reservation_birth_date
 * @property string $reservation_extra_payments
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $expire_at
 * @property bool $sold_out
 * @property int|null $sponsor_organization_id
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Bookmark[] $bookmarks
 * @property-read int|null $bookmarks_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundProviderChat[] $fund_provider_chats
 * @property-read int|null $fund_provider_chats_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundProviderProduct[] $fund_provider_products
 * @property-read int|null $fund_provider_products_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundProvider[] $fund_providers
 * @property-read int|null $fund_providers_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Fund[] $funds
 * @property-read int|null $funds_count
 * @property-read string $description_html
 * @property-read bool $expired
 * @property-read string $price_discount_locale
 * @property-read string $price_locale
 * @property-read bool $reservation_address_is_required
 * @property-read bool $reservation_birth_date_is_required
 * @property-read bool $reservation_phone_is_required
 * @property-read int|null $stock_amount
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\EventLogService\Models\EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read \Illuminate\Database\Eloquent\Collection|Media[] $medias
 * @property-read int|null $medias_count
 * @property-read \App\Models\Organization $organization
 * @property-read Media|null $photo
 * @property-read \App\Models\ProductCategory $product_category
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundProviderProductExclusion[] $product_exclusions
 * @property-read int|null $product_exclusions_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductReservation[] $product_reservations
 * @property-read int|null $product_reservations_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductReservation[] $product_reservations_pending
 * @property-read int|null $product_reservations_pending_count
 * @property-read \App\Models\Organization|null $sponsor_organization
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\VoucherTransaction[] $voucher_transactions
 * @property-read int|null $voucher_transactions_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Voucher[] $vouchers
 * @property-read int|null $vouchers_count
 * @method static Builder|Product newModelQuery()
 * @method static Builder|Product newQuery()
 * @method static Builder|Product onlyTrashed()
 * @method static Builder|Product query()
 * @method static Builder|Product whereAlternativeText($value)
 * @method static Builder|Product whereCreatedAt($value)
 * @method static Builder|Product whereDeletedAt($value)
 * @method static Builder|Product whereDescription($value)
 * @method static Builder|Product whereDescriptionText($value)
 * @method static Builder|Product whereExpireAt($value)
 * @method static Builder|Product whereId($value)
 * @method static Builder|Product whereName($value)
 * @method static Builder|Product whereOrganizationId($value)
 * @method static Builder|Product wherePrice($value)
 * @method static Builder|Product wherePriceDiscount($value)
 * @method static Builder|Product wherePriceType($value)
 * @method static Builder|Product whereProductCategoryId($value)
 * @method static Builder|Product whereReservationAddress($value)
 * @method static Builder|Product whereReservationBirthDate($value)
 * @method static Builder|Product whereReservationEnabled($value)
 * @method static Builder|Product whereReservationExtraPayments($value)
 * @method static Builder|Product whereReservationFields($value)
 * @method static Builder|Product whereReservationPhone($value)
 * @method static Builder|Product whereReservationPolicy($value)
 * @method static Builder|Product whereShowOnWebshop($value)
 * @method static Builder|Product whereSoldOut($value)
 * @method static Builder|Product whereSponsorOrganizationId($value)
 * @method static Builder|Product whereTotalAmount($value)
 * @method static Builder|Product whereUnlimitedStock($value)
 * @method static Builder|Product whereUpdatedAt($value)
 * @method static Builder|Product withTrashed()
 * @method static Builder|Product withoutTrashed()
 * @mixin \Eloquent
 */
class Product extends BaseModel
{
    use HasMedia, SoftDeletes, HasLogs, HasMarkdownDescription, HasBookmarks;

    public const EVENT_CREATED = 'created';
    public const EVENT_SOLD_OUT = 'sold_out';
    public const EVENT_EXPIRED = 'expired';
    public const EVENT_RESERVED = 'reserved';

    public const EVENT_APPROVED = 'approved';
    public const EVENT_REVOKED = 'revoked';

    public const PRICE_TYPE_FREE = 'free';
    public const PRICE_TYPE_REGULAR = 'regular';
    public const PRICE_TYPE_DISCOUNT_FIXED = 'discount_fixed';
    public const PRICE_TYPE_DISCOUNT_PERCENTAGE = 'discount_percentage';

    public const RESERVATION_POLICY_ACCEPT = 'accept';
    public const RESERVATION_POLICY_REVIEW = 'review';
    public const RESERVATION_POLICY_GLOBAL = 'global';

    public const RESERVATION_FIELD_REQUIRED = 'required';
    public const RESERVATION_FIELD_OPTIONAL = 'optional';
    public const RESERVATION_FIELD_GLOBAL = 'global';
    public const RESERVATION_FIELD_NO = 'no';

    public const RESERVATION_EXTRA_PAYMENT_GLOBAL = 'global';
    public const RESERVATION_EXTRA_PAYMENT_YES = 'yes';
    public const RESERVATION_EXTRA_PAYMENT_NO = 'no';

    public const RESERVATION_FIELDS_PRODUCT = [
        self::RESERVATION_FIELD_REQUIRED,
        self::RESERVATION_FIELD_OPTIONAL,
        self::RESERVATION_FIELD_GLOBAL,
        self::RESERVATION_FIELD_NO,
    ];

    public const RESERVATION_FIELDS_ORGANIZATION = [
        self::RESERVATION_FIELD_REQUIRED,
        self::RESERVATION_FIELD_OPTIONAL,
        self::RESERVATION_FIELD_NO,
    ];

    public const RESERVATION_POLICIES = [
        self::RESERVATION_POLICY_ACCEPT,
        self::RESERVATION_POLICY_REVIEW,
        self::RESERVATION_POLICY_GLOBAL,
    ];

    public const RESERVATION_EXTRA_PAYMENT_OPTIONS = [
        self::RESERVATION_EXTRA_PAYMENT_GLOBAL,
        self::RESERVATION_EXTRA_PAYMENT_YES,
        self::RESERVATION_EXTRA_PAYMENT_NO,
    ];

    public const PRICE_DISCOUNT_TYPES = [
        self::PRICE_TYPE_DISCOUNT_FIXED,
        self::PRICE_TYPE_DISCOUNT_PERCENTAGE,
    ];

    public const PRICE_TYPES = [
        self::PRICE_TYPE_FREE,
        self::PRICE_TYPE_REGULAR,
        self::PRICE_TYPE_DISCOUNT_FIXED,
        self::PRICE_TYPE_DISCOUNT_PERCENTAGE,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'description', 'description_text', 'organization_id', 'product_category_id',
        'price', 'total_amount', 'expire_at', 'sold_out',
        'unlimited_stock', 'price_type', 'price_discount', 'sponsor_organization_id',
        'reservation_enabled', 'reservation_policy', 'reservation_extra_payments',
        'reservation_phone', 'reservation_address', 'reservation_birth_date', 'alternative_text',
        'reservation_fields',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'unlimited_stock' => 'boolean',
        'reservation_fields' => 'boolean',
        'reservation_enabled' => 'boolean',
        'expire_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function sponsor_organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'sponsor_organization_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function product_reservations(): HasMany
    {
        return $this->hasMany(ProductReservation::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function product_reservations_pending(): HasMany
    {
        return $this->product_reservations()->whereIn('state', [
            ProductReservation::STATE_WAITING,
            ProductReservation::STATE_PENDING,
            ProductReservation::STATE_ACCEPTED
        ])->whereDoesntHave('voucher_transaction');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function voucher_transactions(): HasMany
    {
        return $this->hasMany(VoucherTransaction::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product_category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function funds(): HasManyThrough
    {
        return $this->hasManyThrough(Fund::class, FundProduct::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function fund_providers(): BelongsToMany
    {
        return $this->belongsToMany(FundProvider::class, 'fund_provider_products')
            ->whereNull('fund_provider_products.deleted_at');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fund_provider_products(): HasMany
    {
        return $this->hasMany(FundProviderProduct::class);
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function product_exclusions(): HasMany
    {
        return $this->hasMany(FundProviderProductExclusion::class);
    }

    /**
     * Get fund logo
     * @return MorphOne
     */
    public function photo(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')->where([
            'type' => 'product_photo'
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fund_provider_chats(): HasMany
    {
        return $this->hasMany(FundProviderChat::class);
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function getReservationPhoneIsRequiredAttribute(): bool
    {
        if (!$this->reservation_fields) {
            return false;
        }

        if ($this->reservation_phone === self::RESERVATION_FIELD_GLOBAL) {
            return $this->organization->reservation_phone === self::RESERVATION_FIELD_REQUIRED;
        }

        return $this->reservation_phone === self::RESERVATION_FIELD_REQUIRED;
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function getReservationAddressIsRequiredAttribute(): bool
    {
        if (!$this->reservation_fields) {
            return false;
        }

        if ($this->reservation_address === self::RESERVATION_FIELD_GLOBAL) {
            return $this->organization->reservation_address === self::RESERVATION_FIELD_REQUIRED;
        }

        return $this->reservation_address === self::RESERVATION_FIELD_REQUIRED;
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function getReservationBirthDateIsRequiredAttribute(): bool
    {
        if (!$this->reservation_fields) {
            return false;
        }

        if ($this->reservation_birth_date === self::RESERVATION_FIELD_GLOBAL) {
            return $this->organization->reservation_birth_date === self::RESERVATION_FIELD_REQUIRED;
        }

        return $this->reservation_birth_date === self::RESERVATION_FIELD_REQUIRED;
    }

    /**
     * @param Fund $fund
     * @param string|bool $voucherBalance
     * @return bool
     */
    public function reservationExtraPaymentsEnabled(
        Fund $fund,
        string|bool $voucherBalance = false,
    ): bool {
        if ($this->reservation_extra_payments === self::RESERVATION_EXTRA_PAYMENT_GLOBAL) {
            $allowed = $this->organization->reservation_allow_extra_payments;
        } else {
            $allowed = $this->reservation_extra_payments === self::RESERVATION_EXTRA_PAYMENT_YES;
        }

        if (!$allowed || $fund->isTypeSubsidy() || !$this->organization->canReceiveExtraPayments()) {
            return false;
        }

        $extraPaymentsAllowed = $this->organization
            ->fund_providers_allowed_extra_payments
            ->filter(fn(FundProvider $provider) => $provider->fund_id === $fund->id)
            ->isNotEmpty();

        $extraPaymentsFullAllowed = $voucherBalance !== false ? $this->organization
            ->fund_providers_allowed_extra_payments_full
            ->filter(fn(FundProvider $provider) => $provider->fund_id === $fund->id)
            ->isNotEmpty() : null;

        $voucherBalanceIsValid =
            $voucherBalance === false ||
            ($extraPaymentsFullAllowed || ($voucherBalance >= 0.1));

        return $extraPaymentsAllowed && $voucherBalanceIsValid;
    }

    /**
     * The product is sold out
     *
     * @param $value
     * @return bool
     * @noinspection PhpUnused
     */
    public function getSoldOutAttribute($value): bool
    {
        return (bool) $value;
    }

    /**
     * The product is expired
     *
     * @return bool
     * @noinspection PhpUnused
     */
    public function getExpiredAttribute(): bool {
        return $this->expire_at && $this->expire_at->isPast();
    }

    /**
     * Count vouchers generated for this product but not used
     *
     * @param Fund|null $fund
     * @return int
     */
    public function countReserved(?Fund $fund = null): int
    {
        return $this->product_reservations_pending()->where(function (Builder $builder) use ($fund) {
            if ($fund) {
                $builder->whereRelation('voucher', 'fund_id', $fund->id);
            }
        })->count();
    }

    /**
     * Count vouchers generated for this product but not used
     *
     * @param Fund|null $fund
     * @return int
     */
    public function countReservedCached(?Fund $fund = null): int
    {
        return $this->product_reservations_pending->filter(function(ProductReservation $reservation) use ($fund) {
            return !$fund || $reservation->voucher->fund_id === $fund->id;
        })->count();
    }

    /**
     * Count actually sold products
     *
     * @param Fund|null $fund
     * @return int
     */
    public function countSold(?Fund $fund = null): int
    {
        return $this->voucher_transactions()->where(function (Builder $builder) use ($fund) {
            $builder->where('state', '!=', VoucherTransaction::STATE_CANCELED);

            if ($fund) {
                $builder->whereRelation('voucher', 'fund_id', $fund->id);
            }
        })->count();
    }

    /**
     * @return int|null
     * @noinspection PhpUnused
     */
    public function getStockAmountAttribute(): ?int
    {
        if ($this->unlimited_stock) {
            return null;
        }

        return $this->total_amount - ($this->countReservedCached() + $this->voucher_transactions->count());
    }

    /**
     * Update sold out state for the product
     */
    public function updateSoldOutState(): void
    {
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
     * @param array $fundFilters
     * @return Builder
     */
    public static function searchQuery(array $fundFilters = []): Builder
    {
        $query = self::query();
        $activeFunds = Implementation::activeFundsQuery()->where($fundFilters)->pluck('id')->toArray();

        // only in stock and not expired
        $query = ProductQuery::inStockAndActiveFilter($query);

        // only approved by at least one sponsor
        return ProductQuery::approvedForFundsFilter($query, $activeFunds);
    }

    /**
     * @param Request $request
     * @return Builder
     */
    public static function searchSample(Request $request): Builder
    {
        $query = self::searchQuery();

        if ($request->input('fund_type')) {
            $query = self::filterFundType($query, $request->input('fund_type'));
        }

        return $query->inRandomOrder();
    }

    /**
     * @param array $options
     * @param Builder|Product|null $builder
     * @return Builder|Product
     */
    public static function search(array $options, Builder|Product $builder = null): Builder|Product
    {
        /** @var Builder|Product $query */
        $query = $builder ?: self::searchQuery();

        if ($fund_type = Arr::get($options, 'fund_type')) {
            $query = self::filterFundType($query, $fund_type);
        }

        if ($product_category_id = Arr::get($options, 'product_category_id')) {
            $query = ProductQuery::productCategoriesFilter($query, $product_category_id);
        }

        if ($fund_id = Arr::get($options, 'fund_id')) {
            $query = ProductQuery::approvedForFundsFilter($query, $fund_id);
        }

        if ($price_type = Arr::get($options, 'price_type')) {
            $query = $query->where('price_type', $price_type);
        }

        if (filter_bool(Arr::get($options, 'unlimited_stock'))) {
            return ProductQuery::unlimitedStockFilter($query, Arr::get($options, 'unlimited_stock'));
        }

        if ($organization_id = Arr::get($options, 'organization_id')) {
            $query = $query->where('organization_id', $organization_id);
        }

        $query = ProductQuery::addPriceMinAndMaxColumn($query);

        if ($q = Arr::get($options, 'q')) {
            ProductQuery::queryDeepFilter($query, $q);
        }

        if (array_get($options, 'postcode') && array_get($options, 'distance')) {
            $geocodeService = resolve('geocode_api');
            $location = $geocodeService->getLocation(array_get($options, 'postcode') . ', Netherlands');

            $query->whereHas('organization.offices', static function (Builder $builder) use ($location, $options) {
                OfficeQuery::whereDistance($builder, (int) array_get($options, 'distance'), [
                    'lat' => $location ? $location['lat'] : 0,
                    'lng' => $location ? $location['lng'] : 0,
                ]);
            });
        }

        $orderBy = Arr::get($options, 'order_by', 'created_at');
        $orderBy = $orderBy === 'most_popular' ? 'voucher_transactions_count' : $orderBy;
        $orderDir = Arr::get($options, 'order_dir', 'desc');

        return $query
            ->withCount('voucher_transactions')
            ->orderBy($orderBy, $orderDir)
            ->orderBy('price_type')
            ->orderBy('price_discount')
            ->orderBy('created_at', 'desc');
    }

    /**
     * @param Builder $builder
     * @param string $fundType
     * @return Builder
     */
    public static function filterFundType(Builder $builder, string $fundType): Builder
    {
        $fundIds = Implementation::activeFundsQuery()->where([
            'type' => $fundType
        ])->pluck('id')->toArray();

        return ProductQuery::approvedForFundsAndActiveFilter($builder, $fundIds);
    }

    /**
     * @param Request $request
     * @param Builder|null $query
     * @return Builder
     */
    public static function searchAny(Request $request, Builder $query = null): Builder {
        $query = $query ?: self::query();

        // filter by unlimited stock
        if ($request->has('unlimited_stock')) {
            ProductQuery::unlimitedStockFilter($query, filter_bool($request->input('unlimited_stock')));
        }

        // filter by string query
        if ($request->has('q') && !empty($q = $request->input('q'))) {
            ProductQuery::queryFilter($query, $q);
        }

        // filter by string query
        if ($request->has('source') && !empty($source = $request->input('source'))) {
            if ($source === 'sponsor') {
                $query->whereNotNull('sponsor_organization_id');
            } elseif ($source === 'provider') {
                $query->whereNull('sponsor_organization_id');
            } elseif ($source === 'archive') {
                $query = TrashedQuery::onlyTrashed($query);
            }
        }

        $orderBy = $request->get('order_by', 'created_at');
        $orderDir = $request->get('order_dir', 'desc');

        if ($orderBy === 'stock_amount') {
            $query = ProductQuery::stockAmountSubQuery($query);
        }

        return $query->orderBy($orderBy, $orderDir);
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getPriceLocaleAttribute(): string
    {
        return $this->priceLocale();
    }

    /**
     * @param Implementation|null $implementation
     * @return string
     */
    public function priceLocale(?Implementation $implementation = null): string
    {
        switch ($this->price_type) {
            case self::PRICE_TYPE_REGULAR: return currency_format_locale($this->price, $implementation);
            case self::PRICE_TYPE_FREE: return 'Gratis';
            case self::PRICE_TYPE_DISCOUNT_FIXED:
            case self::PRICE_TYPE_DISCOUNT_PERCENTAGE: {
                return 'Korting: ' . $this->price_discount_locale;
            }
        }

        return '';
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getPriceDiscountLocaleAttribute(): string
    {
        switch ($this->price_type) {
            case self::PRICE_TYPE_DISCOUNT_FIXED: {
                return currency_format_locale($this->price_discount);
            }
            case self::PRICE_TYPE_DISCOUNT_PERCENTAGE: {
                $isWhole = ($this->price_discount - round($this->price_discount)) === 0.0;

                return currency_format($this->price_discount, $isWhole ? 0 : 2) . '%';
            }
        }

        return '';
    }

    /**
     * @param Fund $fund
     * @return FundProviderProduct|null
     */
    public function getFundProviderProduct(Fund $fund): ?FundProviderProduct
    {
        /** @var FundProviderProduct $query */
        $query = $this->fund_provider_products()->whereRelation('fund_provider.fund', [
            'id' => $fund->id,
        ])->latest();

        return $query->first();
    }

    /**
     * @param Fund $fund
     * @param int $errorCode
     * @return FundProviderProduct
     */
    public function getFundProviderProductOrFail(
        Fund $fund,
        int $errorCode = 403
    ): FundProviderProduct {
        if (!$fundProviderProduct = $this->getFundProviderProduct($fund)) {
            abort($errorCode);
        }

        return $fundProviderProduct;
    }

    /**
     * @param Request $request
     */
    public function updateExclusions(Request $request): void
    {
        foreach ($request->input('disable_funds', []) as $fund_id) {
            /** @var FundProvider $fundProvider */
            if ($fundProvider = $this->organization->fund_providers()->where([
                'fund_id' => $fund_id
            ])->first()) {
                $fundProvider->product_exclusions()->firstOrCreate([
                    'product_id' => $this->id
                ]);

                $fundProvider->fund_provider_products()->where([
                    'product_id' => $this->id
                ])->delete();
            }
        }

        foreach ($request->input('enable_funds', []) as $fund_id) {
            /** @var FundProvider $fundProvider */
            if ($fundProvider = $this->organization->fund_providers()->where([
                'fund_id' => $fund_id
            ])->first()) {
                $fundProvider->product_exclusions()->where([
                    'product_id' => $this->id
                ])->delete();
            }
        }
    }

    /**
     * @return void
     */
    public function resetSubsidyApprovals(): void
    {
        $subsidyFunds = FundQuery::whereProductsAreApprovedAndActiveFilter(Fund::query(), $this)->where([
            'type' =>  Fund::TYPE_SUBSIDIES
        ])->get();

        $subsidyFunds->each(function(Fund $fund) {
            FundProductSubsidyRemovedNotification::send(
                $fund->log($fund::EVENT_PRODUCT_SUBSIDY_REMOVED, [
                    'product'  => $this,
                    'fund'     => $fund,
                    'sponsor'  => $fund->organization,
                    'provider' => $this->organization
            ]));
        });

        $this->fund_provider_products()->whereHas('fund_provider', function(Builder $builder) {
            $builder->whereHas('fund', function(Builder $builder) {
                $builder->where('type', '=', Fund::TYPE_SUBSIDIES);
            });
        })->delete();
    }

    /**
     * Check if price will change after update
     * @param string $price_type
     * @param float $price
     * @param float $price_discount
     * @return bool
     */
    public function priceWillChanged(string $price_type, float $price, float $price_discount): bool
    {
        if ($price_type !== $this->price_type) {
            return true;
        }

        if ($this->price_type === self::PRICE_TYPE_REGULAR &&
            currency_format($price) !== currency_format($this->price)) {
            return true;
        }

        if (in_array($this->price_type, self::PRICE_DISCOUNT_TYPES, true) &&
            currency_format($price_discount) !== currency_format($this->price_discount)) {
            return true;
        }

        return false;
    }

    /**
     * @param Organization $organization
     * @param BaseFormRequest $request
     * @return $this
     */
    public static function storeFromRequest(Organization $organization, BaseFormRequest $request): self
    {
        $price_type = $request->input('price_type');
        $total_amount = $request->input('total_amount');
        $unlimited_stock = $request->input('unlimited_stock', false);
        $price = $price_type === self::PRICE_TYPE_REGULAR ? $request->input('price') : 0;

        $price_discount = in_array($price_type, [
            self::PRICE_TYPE_DISCOUNT_FIXED,
            self::PRICE_TYPE_DISCOUNT_PERCENTAGE
        ], true) ? $request->input('price_discount') : 0;

        /** @var Product $product */
        $product = $organization->products()->create([
            ...$request->only([
                'name', 'description', 'price', 'product_category_id', 'expire_at',
                'reservation_enabled', 'reservation_policy', 'reservation_phone',
                'reservation_address', 'reservation_birth_date', 'alternative_text',
                'reservation_fields',
            ]),
            ...$organization->canReceiveExtraPayments() ? $request->only([
                'reservation_extra_payments',
            ]) : [],
            'total_amount' => $unlimited_stock ? 0 : $total_amount,
            'unlimited_stock' => $unlimited_stock,
            ...compact('price', 'price_type', 'price_discount'),
        ]);

        return $product->attachMediaByUid($request->input('media_uid'));
    }

    /**
     * @param BaseFormRequest $request
     * @return $this
     */
    public function updateFromRequest(BaseFormRequest $request): self
    {
        $price_type = $request->input('price_type');
        $total_amount = $request->input('total_amount');
        $price = $price_type === self::PRICE_TYPE_REGULAR ? $request->input('price') : 0;

        $price_discount = in_array($price_type, [
            self::PRICE_TYPE_DISCOUNT_FIXED,
            self::PRICE_TYPE_DISCOUNT_PERCENTAGE
        ], true) ? $request->input('price_discount') : 0;

        $this->attachMediaByUid($request->input('media_uid'));

        if ($this->priceWillChanged($price_type, $price, $price_discount)) {
            $this->resetSubsidyApprovals();
        }

        return $this->updateModel([
            ...$request->only([
                'name', 'description', 'sold_amount', 'product_category_id', 'expire_at',
                'reservation_enabled', 'reservation_policy', 'reservation_phone',
                'reservation_address', 'reservation_birth_date', 'alternative_text',
                'reservation_fields',
            ]),
            ...$this->organization->canReceiveExtraPayments() ? $request->only([
                'reservation_extra_payments',
            ]) : [],
            'total_amount' => $this->unlimited_stock ? 0 : $total_amount,
            ...compact('price', 'price_type', 'price_discount')
        ]);
    }

    /**
     * @param Fund $fund
     * @return bool
     */
    public function reservationsEnabled(Fund $fund): bool
    {
        return $this->reservation_enabled && (
            ($fund->isTypeSubsidy() && $this->organization->reservations_subsidy_enabled) ||
            ($fund->isTypeBudget() && $this->organization->reservations_budget_enabled));
    }

    /**
     * @param Fund $fund
     * @return bool
     */
    public function autoAcceptsReservations(Fund $fund): bool
    {
        $reservationsEnabled = $this->reservationsEnabled($fund);

        if ($reservationsEnabled && $this->reservation_policy === self::RESERVATION_POLICY_ACCEPT) {
            return true;
        }

        return $reservationsEnabled &&
            ($this->reservation_policy === self::RESERVATION_POLICY_GLOBAL) &&
            $this->organization->reservations_auto_accept;
    }

    /**
     * @param Identity|null $identity
     * @return bool
     */
    public function isBookmarkedBy(?Identity $identity = null): bool
    {
        return $identity && $this->bookmarks->where('identity_address', $identity->address)->isNotEmpty();
    }
}
