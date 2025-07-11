<?php

namespace App\Models;

use App\Scopes\Builders\ProductSubQuery;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\FundProviderProduct.
 *
 * @property int $id
 * @property int $fund_provider_id
 * @property int $product_id
 * @property string $payment_type
 * @property bool $allow_scanning
 * @property int|null $limit_total
 * @property bool $limit_total_unlimited
 * @property int|null $limit_per_identity
 * @property bool $limit_per_identity_unlimited
 * @property string|null $amount
 * @property string|null $price
 * @property \Illuminate\Support\Carbon|null $expire_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\FundProvider|null $fund_provider
 * @property-read bool $active
 * @property-read string $amount_locale
 * @property-read string $payment_type_locale
 * @property-read float $user_price
 * @property-read string $user_price_locale
 * @property-read \App\Models\Product|null $product
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductReservation[] $product_reservations
 * @property-read int|null $product_reservations_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductReservation[] $product_reservations_pending
 * @property-read int|null $product_reservations_pending_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\VoucherTransaction[] $voucher_transactions
 * @property-read int|null $voucher_transactions_count
 * @method static Builder<static>|FundProviderProduct newModelQuery()
 * @method static Builder<static>|FundProviderProduct newQuery()
 * @method static Builder<static>|FundProviderProduct onlyTrashed()
 * @method static Builder<static>|FundProviderProduct query()
 * @method static Builder<static>|FundProviderProduct whereAllowScanning($value)
 * @method static Builder<static>|FundProviderProduct whereAmount($value)
 * @method static Builder<static>|FundProviderProduct whereCreatedAt($value)
 * @method static Builder<static>|FundProviderProduct whereDeletedAt($value)
 * @method static Builder<static>|FundProviderProduct whereExpireAt($value)
 * @method static Builder<static>|FundProviderProduct whereFundProviderId($value)
 * @method static Builder<static>|FundProviderProduct whereId($value)
 * @method static Builder<static>|FundProviderProduct whereLimitPerIdentity($value)
 * @method static Builder<static>|FundProviderProduct whereLimitPerIdentityUnlimited($value)
 * @method static Builder<static>|FundProviderProduct whereLimitTotal($value)
 * @method static Builder<static>|FundProviderProduct whereLimitTotalUnlimited($value)
 * @method static Builder<static>|FundProviderProduct wherePaymentType($value)
 * @method static Builder<static>|FundProviderProduct wherePrice($value)
 * @method static Builder<static>|FundProviderProduct whereProductId($value)
 * @method static Builder<static>|FundProviderProduct whereUpdatedAt($value)
 * @method static Builder<static>|FundProviderProduct withTrashed()
 * @method static Builder<static>|FundProviderProduct withoutTrashed()
 * @mixin \Eloquent
 */
class FundProviderProduct extends BaseModel
{
    use SoftDeletes;

    public const string PAYMENT_TYPE_BUDGET = 'budget';
    public const string PAYMENT_TYPE_SUBSIDY = 'subsidy';

    public const array PAYMENT_TYPES = [
        self::PAYMENT_TYPE_BUDGET,
        self::PAYMENT_TYPE_SUBSIDY,
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'product_id', 'fund_provider_id', 'price', 'old_price', 'amount', 'expire_at',
        'limit_total', 'limit_total_unlimited', 'limit_per_identity', 'limit_per_identity_unlimited',
        'payment_type', 'allow_scanning',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'expire_at' => 'datetime',
        'allow_scanning' => 'bool',
        'limit_total_unlimited' => 'bool',
        'limit_per_identity_unlimited' => 'bool',
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
        if ($this->isPaymentTypeSubsidy()) {
            return $this->getUserPrice($this->product->price);
        }

        return $this->product->price;
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
            case $this->product::PRICE_TYPE_FREE: return trans('prices.free');
            case $this->product::PRICE_TYPE_DISCOUNT_FIXED:
            case $this->product::PRICE_TYPE_DISCOUNT_PERCENTAGE: {
                return trans('prices.discount', [
                    'amount' => $this->getPriceDiscountLocale($price_type, $price_discount),
                ]);
            }
            default: return '';
        }
    }

    /**
     * @return string
     */
    public function getAmountLocaleAttribute(): string
    {
        return currency_format_locale($this->amount ?: 0);
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
                $isWhole = (float) ($price_discount - round($price_discount)) === 0.0;

                return currency_format($price_discount, $isWhole ? 0 : 2) . '%';
            }
            default: return '';
        }
    }

    /**
     * @param Voucher $voucher
     * @throws Exception
     * @return int|null
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

    /**
     * @return bool
     */
    public function isPaymentTypeSubsidy(): bool
    {
        return $this->payment_type === self::PAYMENT_TYPE_SUBSIDY;
    }

    /**
     * @return bool
     */
    public function isPaymentTypeBudget(): bool
    {
        return $this->payment_type === self::PAYMENT_TYPE_BUDGET;
    }

    /**
     * @return string
     */
    protected function getPaymentTypeLocaleAttribute(): string
    {
        return match ($this->payment_type) {
            self::PAYMENT_TYPE_BUDGET => 'Budget',
            self::PAYMENT_TYPE_SUBSIDY => 'Subsidie',
            default => null,
        };
    }
}
