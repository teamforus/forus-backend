<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property int $id
 * @property int $fund_id
 * @property string $type
 * @property string $state
 * @property int $limit
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $fund
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundProductLimitProduct[] $fund_products
 * @property-read int|null $fund_products_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Product[] $products
 * @property-read int|null $products_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProductLimit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProductLimit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProductLimit query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProductLimit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProductLimit whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProductLimit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProductLimit whereLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProductLimit whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProductLimit whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProductLimit whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundProductLimit extends Model
{
    public const string STATE_ACTIVE = 'active';
    public const string STATE_INACTIVE = 'inactive';

    public const string TYPE_ALL = 'all';
    public const string TYPE_SELECTED = 'selected';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fund_id', 'type', 'state', 'limit',
    ];

    /**
     * @return BelongsTo
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return HasMany
     */
    public function fund_products(): HasMany
    {
        return $this->hasMany(FundProductLimitProduct::class);
    }

    /**
     * @return HasManyThrough
     */
    public function products(): HasManyThrough
    {
        return $this->hasManyThrough(
            Product::class,
            FundProductLimitProduct::class,
            'fund_product_limit_id',
            'id',
            'id',
            'product_id',
        );
    }

    /**
     * @param array $productIds
     * @return $this
     */
    public function updateProducts(array $productIds = []): self
    {
        $this->fund_products()->whereNotIn('product_id', $productIds)->delete();

        foreach ($productIds as $id) {
            $this->fund_products()->firstOrCreate(['product_id' => $id]);
        }

        return $this;
    }
}
