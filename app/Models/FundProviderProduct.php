<?php

namespace App\Models;

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
 * @property-read \App\Models\FundProvider $fund_provider
 * @property-read float $user_price
 * @property-read string $user_price_locale
 * @property-read \App\Models\Product $product
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\VoucherTransaction[] $voucher_transactions
 * @property-read int|null $voucher_transactions_count
 * @method static Builder|FundProviderProduct newModelQuery()
 * @method static Builder|FundProviderProduct newQuery()
 * @method static \Illuminate\Database\Query\Builder|FundProviderProduct onlyTrashed()
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
 * @method static \Illuminate\Database\Query\Builder|FundProviderProduct withTrashed()
 * @method static \Illuminate\Database\Query\Builder|FundProviderProduct withoutTrashed()
 * @mixin \Eloquent
 */
class FundProviderProduct extends Model
{
    use SoftDeletes;

    /**
     * @var string[]
     */
    protected $fillable = [
        'product_id', 'fund_provider_id', 'limit_total', 'limit_total_unlimited',
        'limit_per_identity', 'price', 'old_price', 'amount', 'expire_at'
    ];

    protected $casts = [
        'limit_total_unlimited' => 'bool',
    ];

    protected $dates = [
        'expire_at'
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
        return $this->hasMany(VoucherTransaction::class);
    }

    /**
     * @return float
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
                $isWhole = (double) ($price_discount - round($price_discount)) === (double) 0;
                return currency_format($price_discount, $isWhole ? 0 : 2) . '%';
            }
            default: return '';
        }
    }

    /**
     * @param string $identity_address
     * @return int|null
     */
    public function stockAvailableForIdentity(string $identity_address): ?int
    {
        if (!$this->fund_provider->fund->isTypeSubsidy()) {
            return null;
        }

        $query = $this->fund_provider->fund->budget_vouchers()->where([
            'identity_address' => $identity_address
        ]);

        $limit_multiplier = $query->exists() ? $query->sum('limit_multiplier') : 1;

        $limit_per_identity = $this->limit_per_identity * $limit_multiplier;
        $limiters = [$limit_per_identity];

        if (!$this->product->unlimited_stock) {
            $limiters[] = $this->product->stock_amount;
        }

        if (!$this->limit_total_unlimited) {
            $limiters[] = $this->limit_total;
        }

        $limitAvailable = (int) collect($limiters)->min();

        $count_transactions = VoucherTransaction::where([
            'product_id' => $this->product_id,
            'organization_id' => $this->product->organization_id,
        ])->whereHas('voucher', function(Builder $builder) use ($identity_address) {
            $builder->where('identity_address', '=', $identity_address);
            $builder->where('fund_id', '=', $this->fund_provider->fund_id);
            $builder->whereDoesntHave('product_reservation');
        })->where('state', '!=', VoucherTransaction::STATE_CANCELED)->count();

        $count_vouchers = Voucher::where([
            'fund_id' => $this->fund_provider->fund_id,
            'product_id' => $this->product_id,
            'identity_address' => $identity_address,
        ])->whereDoesntHave('product_reservation')->count();

        $count_reservations = ProductReservation::where([
            'product_id' => $this->product_id,
        ])->whereNotIn('state', [
            ProductReservation::STATE_CANCELED,
            ProductReservation::STATE_REJECTED,
        ])->whereHas('voucher', function(Builder $builder) use ($identity_address) {
            $builder->where('identity_address', '=', $identity_address);
            $builder->where('fund_id', '=', $this->fund_provider->fund_id);
        })->whereDoesntHave('voucher_transaction')->count();

        return max($limitAvailable - ($count_transactions + $count_vouchers + $count_reservations), 0);
    }
}
