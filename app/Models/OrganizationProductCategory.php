<?php

namespace App\Models;

use Carbon\Carbon;

/**
 * Class FundProductCategory
 * @property mixed $id
 * @property integer $organization_id
 * @property integer $product_category_id
 * @property Fund $fund
 * @property Organization $organization
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
 */
class OrganizationProductCategory extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organization_id', 'product_category_id'
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
    public function product() {
        return $this->belongsTo(ProductCategory::class);
    }
}
