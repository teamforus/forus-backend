<?php

namespace App\Models;

use App\Models\Traits\NodeTrait;
use Dimsav\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

/**
 * App\Models\ProductCategory
 *
 * @property int $id
 * @property string $key
 * @property int|null $parent_id
 * @property int $_lft
 * @property int $_rgt
 * @property int $service
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Kalnoy\Nestedset\Collection|\App\Models\ProductCategory[] $children
 * @property-read int|null $children_count
 * @property-read \Kalnoy\Nestedset\Collection|\App\Models\ProductCategory[] $descendants_with_products
 * @property-read int|null $descendants_with_products_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Fund[] $funds
 * @property-read int|null $funds_count
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Organization[] $organizations
 * @property-read int|null $organizations_count
 * @property-read \App\Models\ProductCategory|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Product[] $products
 * @property-read int|null $products_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductCategoryTranslation[] $translations
 * @property-read int|null $translations_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductCategory d()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductCategory listsTranslations($translationField)
 * @method static \Kalnoy\Nestedset\QueryBuilder|\App\Models\ProductCategory newModelQuery()
 * @method static \Kalnoy\Nestedset\QueryBuilder|\App\Models\ProductCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductCategory notTranslatedIn($locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductCategory orWhereTranslation($key, $value, $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductCategory orWhereTranslationLike($key, $value, $locale = null)
 * @method static \Kalnoy\Nestedset\QueryBuilder|\App\Models\ProductCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductCategory translated()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductCategory translatedIn($locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductCategory whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductCategory whereLft($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductCategory whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductCategory whereRgt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductCategory whereService($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductCategory whereTranslation($key, $value, $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductCategory whereTranslationLike($key, $value, $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductCategory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ProductCategory withTranslation()
 * @mixin \Eloquent
 */
class ProductCategory extends Model
{
    use Translatable, NodeTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key', 'parent_id', 'service',
    ];

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = [];

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    public $translatedAttributes = [
        'name'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products() {
        return $this->hasMany(Product::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function descendants_with_products() {
        return $this->hasMany(ProductCategory::class, 'parent_id')
            ->where(function(Builder $builder) {
                $builder->has('products');
                $builder->orHas('descendants');
            });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function organizations() {
        return $this->belongsToMany(
            Organization::class,
            'organization_product_categories'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function funds() {
        return $this->belongsToMany(
            Fund::class,
            'fund_product_categories'
        );
    }

    /**
     * @param $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function search(Request $request) {
        $query = self::query();
        $parent_id = $request->input('parent_id', false);
        $onlyUsed = $request->input('used', false);

        $disabledCategories = config(
            'forus.product_categories.disabled_top_categories', []
        );

        if ($parent_id) {
            $query->where([
                'parent_id' => $parent_id == 'null' ? null : $parent_id
            ]);
        }

        if ($q = $request->input('q', false)) {
            $query->whereHas('translations', function(
                Builder $builder
            ) use ($q) {
                $builder->where('name', 'LIKE', "%$q%");
            });
        }

        if ($request->has('service')) {
            $query->where('service', '=', !!$request->input('service'));
        }

        if (count($disabledCategories) > 0) {
            $query->whereNotIn('id', $disabledCategories);
        }

        if (!$onlyUsed) {
            return $query;
        }

        // List all used product categories used by active products for
        // current implementation
        $products = Product::searchQuery()->distinct();
        $products = $products->pluck('product_category_id');

        $query->select([
            'id', (new self)->getLftName(), (new self)->getRgtName(),
        ])->with(['descendants_min']);

        $queryHash = hash('md5', $sql_with_bindings = str_replace_array(
            '?', $query->getBindings(), $query->toSql()
        ));

        /** @var ProductCategory[]|Collection $categories */
        $categories = cache_optional($queryHash, function() use ($query) {
            return $query->get();
        }, 120);

        // Only categories with products
        $categories = $categories->filter(function(
            ProductCategory $productCategory
        ) use ($products) {
            $ids = $productCategory->descendants_min->pluck('id');
            $ids->push($productCategory->id);

            return $products->intersect($ids)->count() > 0;
        })->pluck('id');

        return self::whereIn('id', $categories);
    }
}
