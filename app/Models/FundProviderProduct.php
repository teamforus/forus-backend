<?php

namespace App\Models;

use App\Scopes\Builders\ProductSubQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\FundProviderProduct
 *
 * @property int $id
 * @property int $fund_provider_id
 * @property int $product_id
 * @property int|null $limit_total
 * @property bool $limit_total_unlimited
 * @property int|null $limit_per_identity
 * @property string|null $amount
 * @property string|null $price
 * @property \Illuminate\Support\Carbon|null $expire_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\FundProvider|null $fund_provider
 * @property-read bool $active
 * @property-read float $user_price
 * @property-read string $user_price_locale
 * @property-read \App\Models\Product|null $product
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductReservation[] $product_reservations
 * @property-read int|null $product_reservations_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductReservation[] $product_reservations_pending
 * @property-read int|null $product_reservations_pending_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\VoucherTransaction[] $voucher_transactions
 * @property-read int|null $voucher_transactions_count
 * @method static Builder|FundProviderProduct newModelQuery()
 * @method static Builder|FundProviderProduct newQuery()
 * @method static Builder|FundProviderProduct onlyTrashed()
 * @method static Builder|FundProviderProduct query()
 * @method static Builder|FundProviderProduct whereAmount($value)
 * @method static Builder|FundProviderProduct whereCreatedAt($value)
 * @method static Builder|FundProviderProduct whereDeletedAt($value)
 * @method static Builder|FundProviderProduct whereExpireAt($value)
 * @method static Builder|FundProviderProduct whereFundProviderId($value)
 * @method static Builder|FundProviderProduct whereId($value)
 * @method static Builder|FundProviderProduct whereLimitPerIdentity($value)
 * @method static Builder|FundProviderProduct whereLimitTotal($value)
 * @method static Builder|FundProviderProduct whereLimitTotalUnlimited($value)
 * @method static Builder|FundProviderProduct wherePrice($value)
 * @method static Builder|FundProviderProduct whereProductId($value)
 * @method static Builder|FundProviderProduct whereUpdatedAt($value)
 * @method static Builder|FundProviderProduct withTrashed()
 * @method static Builder|FundProviderProduct withoutTrashed()
 * @mixin \Eloquent
 */
class FundProviderProduct extends BaseModel
{
    use SoftDeletes;

    /**
     * @var string[]
     */
    protected $fillable = [
        'product_id', 'fund_provider_id', 'limit_total', 'limit_total_unlimited',
        'limit_per_identity', 'price', 'old_price', 'amount', 'expire_at',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'expire_at' => 'datetime',
        'limit_total_unlimited' => 'bool',
    ];

    /**
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo
     */
    public function fund_provider(): BelongsTo
    {
        return $this->belongsTo(FundProvider::class);
    }

    /**
     * @return HasMany
     */
    public function voucher_transactions(): HasMany
    {
        return $this
            ->hasMany(VoucherTransaction::class)
            ->where('state', '!=', VoucherTransaction::STATE_CANCELED);
    }

    /**
     * @return HasMany
     */
    public function product_reservations(): HasMany
    {
        return $this->hasMany(ProductReservation::class);
    }

    /**
     * @return HasMany
     */
    public function product_reservations_pending(): HasMany
    {
        return $this
            ->hasMany(ProductReservation::class)
            ->where('state', ProductReservation::STATE_PENDING);
    }

    /**
     * @return float
     * @noinspection PhpUnused
     */
    public function getUserPriceAttribute(): float
    {
        return $this->getUserPrice($this->product->price);
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getUserPriceLocaleAttribute(): string
    {
        return $this->getUserPriceLocale(
            $this->getUserPrice($this->product->price),
            $this->product->price_type,
            $this->product->price_discount
        );
    }

    /**
     * @param float $price
     * @return float
     */
    public function getUserPrice(float $price): float
    {
        return currency_format(max(0, $price - $this->amount));
    }

    /**
     * @param float $price
     * @param string $price_type
     * @param string $price_discount
     * @return string
     */
    public function getUserPriceLocale(
        float $price,
        string $price_type,
        string $price_discount
    ): string {
        switch ($price_type) {
            case $this->product::PRICE_TYPE_REGULAR: return currency_format_locale($price);
            case $this->product::PRICE_TYPE_FREE: return 'Gratis';
            case $this->product::PRICE_TYPE_DISCOUNT_FIXED:
            case $this->product::PRICE_TYPE_DISCOUNT_PERCENTAGE: {
                return 'Korting: ' . $this->getPriceDiscountLocale($price_type, $price_discount);
            }
            default: return '';
        }
    }


    /**
     * @param string $price_type
     * @param string $price_discount
     * @return string
     */
    public function getPriceDiscountLocale(string $price_type, string $price_discount): string
    {
        switch ($price_type) {
            case $this->product::PRICE_TYPE_DISCOUNT_FIXED: return currency_format_locale($price_discount);
            case $this->product::PRICE_TYPE_DISCOUNT_PERCENTAGE: {
                $isWhole = (double) ($price_discount - round($price_discount)) === 0.0;
                return currency_format($price_discount, $isWhole ? 0 : 2) . '%';
            }
            default: return '';
        }
    }

    /**
     * @param Voucher $voucher
     * @return int|null
     * @throws \Exception
     */
    public function stockAvailableForVoucher(Voucher $voucher): ?int
    {
        return (int) max(ProductSubQuery::appendReservationStats([
            'voucher_id' => $voucher->id,
        ], Product::whereId($this->product_id))->first()['limit_available'] ?? 0, 0);
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function getActiveAttribute(): bool
    {
        return !$this->trashed();
    }
}
