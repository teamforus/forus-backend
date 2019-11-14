<?php

namespace App\Models;

/**
 * App\Models\FundProviderProduct
 *
 * @property int $id
 * @property int $fund_provider_id
 * @property int $product_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\FundProvider $fund_provider
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Product[] $products
 * @property-read int|null $products_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderProduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderProduct newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderProduct query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderProduct whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderProduct whereFundProviderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderProduct whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderProduct whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderProduct whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundProviderProduct extends Model
{
    protected $fillable = [
        'product_id', 'fund_provider_id'
    ];

    public function products() {
        return $this->hasMany(Product::class);
    }

    public function fund_provider() {
        return $this->belongsTo(FundProvider::class);
    }
}
