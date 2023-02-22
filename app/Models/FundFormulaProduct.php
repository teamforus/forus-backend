<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\FundFormulaProduct
 *
 * @property int $id
 * @property int $fund_id
 * @property int $product_id
 * @property string $price
 * @property string|null $record_type_key_multiplier
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $fund
 * @property-read \App\Models\Product $product
 * @method static \Illuminate\Database\Eloquent\Builder|FundFormulaProduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundFormulaProduct newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundFormulaProduct query()
 * @method static \Illuminate\Database\Eloquent\Builder|FundFormulaProduct whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundFormulaProduct whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundFormulaProduct whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundFormulaProduct wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundFormulaProduct whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundFormulaProduct whereRecordTypeKeyMultiplier($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundFormulaProduct whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundFormulaProduct extends BaseModel
{
    /**
     * @var array
     */
    protected $fillable = [
        'id', 'fund_id', 'product_id', 'price', 'record_type_key_multiplier',
    ];

    /**
     * @return BelongsTo
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo
     */
    public function record_type(): BelongsTo
    {
        return $this->belongsTo(
            RecordType::class, 'record_type_key_multiplier', 'key'
        );
    }
}
