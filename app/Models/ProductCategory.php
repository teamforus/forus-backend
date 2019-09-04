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
    protected $with = [
        'translations'
    ];

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
        $query =  self::query();

        if ($parent_id = $request->input('parent_id', null)) {
            $query->where([
                'parent_id' => $parent_id == 'null' ? null : $parent_id
            ]);
        }

        if ($q = $request->input('q', false)) {
            $query->whereHas('translations', function(Builder $builder) use ($q) {
                $builder->where('name', 'LIKE', "%$q%");
            });
        }

        if ($request->has('service')) {
            $query->where('service', '=', !!$request->input('service'));
        }

        if ($request->input('used', false)) {
            $query->whereHas('products');
            $query->orWhereHas('descendants.products');
        }

        return $query;
    }
}
