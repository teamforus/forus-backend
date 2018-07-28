<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Organization
 * @property mixed $id
 * @property string $identity_address
 * @property string $name
 * @property string $iban
 * @property string $email
 * @property string $phone
 * @property string $kvk
 * @property string $btw
 * @property Collection $funds
 * @property Collection $products
 * @property Collection $supplied_funds
 * @property Collection $organization_funds
 * @property Collection $product_categories
 * @property Collection $offices
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
 */
class Organization extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'identity_address', 'name', 'iban', 'email', 'phone', 'kvk', 'btw'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function funds() {
        return $this->hasMany(Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products() {
        return $this->hasMany(Product::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function offices() {
        return $this->hasMany(Office::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function product_categories() {
        return $this->belongsToMany(
            ProductCategory::class,
            'organization_product_categories'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function supplied_funds() {
        return $this->belongsToMany(
            Fund::class,
            'organization_funds'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function organization_funds() {
        return $this->hasMany(FundProvider::class);
    }
}
