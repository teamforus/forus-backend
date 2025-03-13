<?php

namespace App\Models;

/**
 * App\Models\ProductCategoryTranslation.
 *
 * @property int $id
 * @property int $product_category_id
 * @property string $locale
 * @property string $name
 * @property-read \App\Models\ProductCategory $product_category
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategoryTranslation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategoryTranslation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategoryTranslation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategoryTranslation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategoryTranslation whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategoryTranslation whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategoryTranslation whereProductCategoryId($value)
 * @mixin \Eloquent
 */
class ProductCategoryTranslation extends BaseModel
{
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product_category()
    {
        return $this->belongsTo(ProductCategory::class);
    }
}
