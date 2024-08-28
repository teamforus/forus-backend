<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\FundAmountPreset
 *
 * @property int $id
 * @property int $fund_id
 * @property string $name
 * @property string $amount
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $fund
 * @property-read string $amount_locale
 * @method static \Illuminate\Database\Eloquent\Builder|FundAmountPreset newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundAmountPreset newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundAmountPreset onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|FundAmountPreset query()
 * @method static \Illuminate\Database\Eloquent\Builder|FundAmountPreset whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundAmountPreset whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundAmountPreset whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundAmountPreset whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundAmountPreset whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundAmountPreset whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundAmountPreset whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundAmountPreset withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|FundAmountPreset withoutTrashed()
 * @mixin \Eloquent
 */
class FundAmountPreset extends Model
{
    use SoftDeletes;

    /**
     * @var string[]
     */
    protected $fillable = [
        'fund_id', 'name', 'amount',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return string
     */
    public function getAmountLocaleAttribute(): string
    {
        return currency_format_locale($this->amount);
    }
}
