<?php

namespace App\Models;

use App\Models\Traits\HasDbTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\FundTopUp
 *
 * @property int $id
 * @property int|null $fund_id
 * @property string $code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund|null $fund
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundTopUpTransaction[] $transactions
 * @property-read int|null $transactions_count
 * @method static \Illuminate\Database\Eloquent\Builder|FundTopUp newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundTopUp newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundTopUp query()
 * @method static \Illuminate\Database\Eloquent\Builder|FundTopUp whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundTopUp whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundTopUp whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundTopUp whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundTopUp whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundTopUp extends Model
{
    use HasDbTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code', 'fund_id',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(FundTopUpTransaction::class);
    }

    /**
     * Generate new top up code
     * @return string
     */
    public static function generateCode(): string
    {
        return static::makeUniqueToken('code', 4, 2);
    }
}
