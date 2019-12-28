<?php

namespace App\Models;

/**
 * App\Models\ProductCategoryTranslation
 *
 * @property int $id
 * @property int $product_category_id
 * @property string $locale
 * @property string $name
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @property-read \App\Models\ProductCategory $product_category
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductCategoryTranslation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductCategoryTranslation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductCategoryTranslation query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductCategoryTranslation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductCategoryTranslation whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductCategoryTranslation whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductCategoryTranslation whereProductCategoryId($value)
 * @mixin \Eloquent
 */
class ProductCategoryTranslation extends Model
{
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product_category() {
        return $this->belongsTo(ProductCategory::class);
    }
}
