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
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @property-read \App\Models\Product $product
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProduct newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProduct query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProduct whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProduct whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProduct whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProduct whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProduct whereUpdatedAt($value)
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
