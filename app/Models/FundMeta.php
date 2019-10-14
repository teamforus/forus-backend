<?php

namespace App\Models;

/**
 * App\Models\FundMeta
 *
 * @property int $id
 * @property int $fund_id
 * @property string $key
 * @property string $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $fund
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundMeta newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundMeta newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundMeta query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundMeta whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundMeta whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundMeta whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundMeta whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundMeta whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundMeta whereValue($value)
 * @mixin \Eloquent
 */
class FundMeta extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fund_id', 'key', 'value'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fund() {
        return $this->belongsTo(Fund::class);
    }
}
