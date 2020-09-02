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
 * @property int|null $limit_per_identity
 * @property float|null $amount
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
        'product_id', 'fund_provider_id', 'limit_total', 'amount', 'limit_per_identity'
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
    public function identityStockAvailable(string $identity_address): ?int {
        $limitAvailable = $this->fund_provider->fund->isTypeSubsidy() ? min(
            $this->limit_per_identity,
            $this->limit_total,
            $this->product->stock_amount
        ) : null;

        if (is_null($limitAvailable)) {
            return null;
        }

        return $limitAvailable - $this->voucher_transactions()->whereHas('voucher', static function(
            Builder $builder) use ($identity_address) {
            $builder->where('identity_address', '=', $identity_address);
        })->count();
    }
}
