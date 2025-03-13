<?php

namespace App\Models;

/**
 * App\Models\FundProduct.
 *
 * @property int $id
 * @property int $fund_id
 * @property int $product_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $fund
 * @property-read \App\Models\Product $product
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProduct newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProduct query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProduct whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProduct whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProduct whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProduct whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProduct whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundProduct extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fund_id', 'product_id',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fund()
    {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
