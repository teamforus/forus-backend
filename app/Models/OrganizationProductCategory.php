<?php

namespace App\Models;

/**
 * App\Models\OrganizationProductCategory
 *
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @property-read \App\Models\Organization $organization
 * @property-read \App\Models\ProductCategory $product
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrganizationProductCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrganizationProductCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrganizationProductCategory query()
 * @mixin \Eloquent
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
