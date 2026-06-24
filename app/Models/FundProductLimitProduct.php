<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $fund_product_limit_id
 * @property int $product_id
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProductLimitProduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProductLimitProduct newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProductLimitProduct query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProductLimitProduct whereFundProductLimitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProductLimitProduct whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundProductLimitProduct whereProductId($value)
 * @mixin \Eloquent
 */
class FundProductLimitProduct extends Model
{
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fund_product_limit_id', 'product_id',
    ];
}
