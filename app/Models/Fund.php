<?php

namespace App\Models;

use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Traits\HasMedia;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Class Fund
 * @property mixed $id
 * @property integer $organization_id
 * @property string $state
 * @property string $name
 * @property Organization $organization
 * @property Media $logo
 * @property Collection $metas
 * @property Collection $products
 * @property Collection $product_categories
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
 */
class Fund extends Model
{
    use HasMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organization_id', 'state', 'name', 'start_date', 'end_date'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization() {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function metas() {
        return $this->hasMany(FundMeta::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function products() {
        return $this->belongsToMany(
            Product::class,
            'fund_products'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function product_categories() {
        return $this->belongsToMany(
            ProductCategory::class,
            'fund_product_categories'
        );
    }

    /**
     * Get fund logo
     * @return MorphOne
     */
    public function logo() {
        return $this->morphOne(Media::class, 'mediable')->where([
            'type' => 'fund_logo'
        ]);
    }
}
