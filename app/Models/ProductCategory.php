<?php

namespace App\Models;

use Carbon\Carbon;
use Dimsav\Translatable\Translatable;
use Illuminate\Database\Eloquent\Collection;

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
    use Translatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key', 'parent_id', 'service'
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent() {
        return $this->belongsTo(ProductCategory::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products() {
        return $this->hasMany(Product::class);
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
}
