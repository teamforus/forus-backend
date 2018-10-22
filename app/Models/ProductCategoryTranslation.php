<?php

namespace App\Models;

/**
 * Class TokenTranslation
 * @property mixed $id
 * @property integer $token_id
 * @property string $locale
 * @property string $name
 * @property Token $token
 * @package App\Models
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
