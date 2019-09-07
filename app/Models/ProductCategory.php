<?php

namespace App\Models;

use App\Models\Traits\EloquentModel;
use App\Models\Traits\NodeTrait;
use Carbon\Carbon;
use Dimsav\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

/**
 * Class ProductCategory
 * @property mixed $id
 * @property string $key
 * @property string $name
 * @property integer $parent_id
 * @property ProductCategory $parent
 * @property Collection $funds
 * @property Collection $products
 * @property Collection $organizations
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
 */
class ProductCategory extends Model
{
    use Translatable, EloquentModel, NodeTrait;

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
