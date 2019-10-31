<?php

namespace App\Models;

/**
 * App\Models\FundFormulaProduct
 *
 * @property int $id
 * @property int|null $fund_id
 * @property int|null $product_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund|null $fund
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @property-read \App\Models\Product|null $product
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundFormulaProduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundFormulaProduct newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundFormulaProduct query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundFormulaProduct whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundFormulaProduct whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundFormulaProduct whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundFormulaProduct whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundFormulaProduct whereUpdatedAt($value)
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
