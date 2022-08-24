<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\FavouriteProduct
 *
 * @property int $product_id
 * @property int $identity_address
 * @property Product $product
 * @property-read Identity|null $identity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class FavouriteProduct extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product_id', 'identity_address'
    ];

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'identity_address', 'address');
    }
}
