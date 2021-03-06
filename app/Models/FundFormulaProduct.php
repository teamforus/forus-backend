<?php

namespace App\Models;

/**
 * App\Models\FundFormulaProduct
 *
 * @property int $id
 * @property int $fund_id
 * @property int $product_id
 * @property string $price
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $fund
 * @property-read \App\Models\Product $product
 * @method static \Illuminate\Database\Eloquent\Builder|FundFormulaProduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundFormulaProduct newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundFormulaProduct query()
 * @method static \Illuminate\Database\Eloquent\Builder|FundFormulaProduct whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundFormulaProduct whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundFormulaProduct whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundFormulaProduct wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundFormulaProduct whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundFormulaProduct whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundFormulaProduct extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'id', 'fund_id', 'product_id', 'price',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fund() {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product() {
        return $this->belongsTo(Product::class);
    }
}
