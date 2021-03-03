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
 * @property float|null $amount
 * @property float|null $price
 * @property \Illuminate\Support\Carbon|null $expiration_date
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\FundProvider $fund_provider
 * @property-read \App\Models\Product $product
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\VoucherTransaction[] $voucher_transactions
 * @property-read int|null $voucher_transactions_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderProduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderProduct newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\FundProviderProduct onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderProduct query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderProduct whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderProduct whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderProduct whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderProduct whereFundProviderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderProduct whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderProduct whereLimitPerIdentity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderProduct whereLimitTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderProduct whereLimitTotalUnlimited($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderProduct wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderProduct whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderProduct whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\FundProviderProduct withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\FundProviderProduct withoutTrashed()
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
        'limit_per_identity', 'price', 'old_price', 'amount', 'expiration_date'
    ];

    protected $casts = [
        'limit_total_unlimited' => 'bool',
    ];

    /**
     * @return BelongsTo
     */
    public function product(): BelongsTo {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo
     */
    public function fund_provider(): BelongsTo {
        return $this->belongsTo(FundProvider::class);
    }

    /**
     * @return HasMany
     */
    public function voucher_transactions(): HasMany {
        return $this->hasMany(VoucherTransaction::class);
    }

    /**
     * @param string $identity_address
     * @return int|null
     */
    public function stockAvailableForIdentity(string $identity_address): ?int {
        if (!$limitAvailable = $this->fund_provider->fund->isTypeSubsidy()) {
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
        ])->whereHas('voucher', static function(Builder $builder) use ($identity_address) {
            $builder->where('identity_address', '=', $identity_address);
        })->count();

        return max($limitAvailable - $count_transactions, 0);
    }
}
