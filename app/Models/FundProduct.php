<?php

namespace App\Models;

/**
 * App\Models\FundProduct
 *
 * @property int $id
 * @property int $fund_id
 * @property int $product_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $fund
 * @property-read \App\Models\Product $product
 * @method static \Illuminate\Database\Eloquent\Builder|FundProduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundProduct newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundProduct query()
 * @method static \Illuminate\Database\Eloquent\Builder|FundProduct whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundProduct whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundProduct whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundProduct whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundProduct whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundProduct extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fund_id', 'product_id'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fund() {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product() {
        return $this->belongsTo(Product::class);
    }
}
