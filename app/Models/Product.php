<?php

namespace App\Models;

use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Traits\HasMedia;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Product
 * @property mixed $id
 * @property string $name
 * @property string $description
 * @property integer $organization_id
 * @property integer $product_category_id
 * @property integer $price
 * @property integer $old_price
 * @property integer $total_amount
 * @property integer $sold_amount
 * @property Organization $organization
 * @property ProductCategory $product_category
 * @property Media $photo
 * @property Collection $funds
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
 */
class Product extends Model
{
    use HasMedia, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'description', 'organization_id', 'product_category_id',
        'price', 'old_price', 'total_amount', 'sold_amount'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'deleted_at'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization() {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product_category() {
        return $this->belongsTo(ProductCategory::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function funds() {
        return $this->hasManyThrough(
            Fund::class,
            FundProduct::class
        );
    }

    /**
     * Get fund logo
     * @return MorphOne
     */
    public function photo() {
        return $this->morphOne(Media::class, 'mediable')->where([
            'type' => 'product_photo'
        ]);
    }

    /**
     * Get the product's price.
     *
     * @param  string  $value
     * @return string
     */
    public function getPriceAttribute($value)
    {
        return round($value, 2);
    }

    /**
     * Get the product's old price.
     *
     * @param  string  $value
     * @return string
     */
    public function getOldPriceAttribute($value)
    {
        return round($value, 2);
    }
}
