<?php

namespace App\Models;

use App\Models\Traits\NodeTrait;
use Astrotomic\Translatable\Translatable;
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
 * @property-read \Kalnoy\Nestedset\Collection|ProductCategory[] $children
 * @property-read int|null $children_count
 * @property-read \Kalnoy\Nestedset\Collection|ProductCategory[] $descendants_with_products
 * @property-read int|null $descendants_with_products_count
 * @property-read Collection|\App\Models\Organization[] $organizations
 * @property-read int|null $organizations_count
 * @property-read ProductCategory|null $parent
 * @property-read Collection|\App\Models\Product[] $products
 * @property-read int|null $products_count
 * @property-read \App\Models\ProductCategoryTranslation|null $translation
 * @property-read Collection|\App\Models\ProductCategoryTranslation[] $translations
 * @property-read int|null $translations_count
 * @method static \Kalnoy\Nestedset\Collection|static[] all($columns = ['*'])
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory ancestorsAndSelf($id, array $columns = [])
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory ancestorsOf($id, array $columns = [])
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory applyNestedSetScope(?string $table = null)
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory countErrors()
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory d()
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory defaultOrder(string $dir = 'asc')
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory descendantsAndSelf($id, array $columns = [])
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory descendantsOf($id, array $columns = [], $andSelf = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory fixSubtree($root)
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory fixTree($root = null)
 * @method static \Kalnoy\Nestedset\Collection|static[] get($columns = ['*'])
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory getNodeData($id, $required = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory getPlainNodeData($id, $required = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory getTotalErrors()
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory hasChildren()
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory hasParent()
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory isBroken()
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory leaves(array $columns = [])
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory listsTranslations(string $translationField)
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory makeGap(int $cut, int $height)
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory moveNode($key, $position)
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory newModelQuery()
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory newQuery()
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory notTranslatedIn(?string $locale = null)
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory orWhereAncestorOf(bool $id, bool $andSelf = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory orWhereDescendantOf($id)
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory orWhereNodeBetween($values)
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory orWhereNotDescendantOf($id)
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory orWhereTranslation(string $translationField, $value, ?string $locale = null)
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory orWhereTranslationLike(string $translationField, $value, ?string $locale = null)
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory orderByTranslation(string $translationField, string $sortMethod = 'asc')
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory query()
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory rebuildSubtree($root, array $data, $delete = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory rebuildTree(array $data, $delete = false, $root = null)
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory reversed()
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory root(array $columns = [])
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory translated()
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory translatedIn(?string $locale = null)
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory whereAncestorOf($id, $andSelf = false, $boolean = 'and')
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory whereAncestorOrSelf($id)
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory whereCreatedAt($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory whereDescendantOf($id, $boolean = 'and', $not = false, $andSelf = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory whereDescendantOrSelf(string $id, string $boolean = 'and', string $not = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory whereId($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory whereIsAfter($id, $boolean = 'and')
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory whereIsBefore($id, $boolean = 'and')
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory whereIsLeaf()
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory whereIsRoot()
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory whereKey($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory whereLft($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory whereNodeBetween($values, $boolean = 'and', $not = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory whereNotDescendantOf($id)
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory whereParentId($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory whereRgt($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory whereService($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory whereTranslation(string $translationField, $value, ?string $locale = null, string $method = 'whereHas', string $operator = '=')
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory whereTranslationLike(string $translationField, $value, ?string $locale = null)
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory whereUpdatedAt($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory withDepth(string $as = 'depth')
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory withTranslation()
 * @method static \Kalnoy\Nestedset\QueryBuilder|ProductCategory withoutRoot()
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
     * @param Request $request
     * @return ProductCategory|\Illuminate\Database\Query\Builder|\Kalnoy\Nestedset\QueryBuilder
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
