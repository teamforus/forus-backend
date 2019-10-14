<?php

namespace App\Models;

/**
 * App\Models\FundProductCategory
 *
 * @property int $id
 * @property int $fund_id
 * @property int $product_category_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $fund
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @property-read \App\Models\ProductCategory $product
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProductCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProductCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProductCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProductCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProductCategory whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProductCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProductCategory whereProductCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProductCategory whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundProductCategory extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fund_id', 'product_category_id'
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
        return $this->belongsTo(ProductCategory::class);
    }
}
