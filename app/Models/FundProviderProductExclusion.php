<?php

namespace App\Models;

use App\Models\Traits\HasFormattedTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\FundProviderProductExclusion
 *
 * @property int $id
 * @property int $fund_provider_id
 * @property int|null $product_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\FundProvider $fund_provider
 * @property-read string|null $created_at_string
 * @property-read string|null $created_at_string_locale
 * @property-read string|null $updated_at_string
 * @property-read string|null $updated_at_string_locale
 * @property-read \App\Models\Product|null $product
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderProductExclusion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderProductExclusion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderProductExclusion query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderProductExclusion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderProductExclusion whereFundProviderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderProductExclusion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderProductExclusion whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderProductExclusion whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundProviderProductExclusion extends Model
{
    use HasFormattedTimestamps;

    protected $fillable = [
        'fund_provider_id', 'product_id'
    ];

    /**
     * @return BelongsTo
     */
    public function fund_provider(): BelongsTo {
        return $this->belongsTo(FundProvider::class);
    }

    /**
     * @return BelongsTo
     */
    public function product(): BelongsTo {
        return $this->belongsTo(Product::class);
    }
}
