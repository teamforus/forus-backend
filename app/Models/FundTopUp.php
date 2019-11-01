<?php

namespace App\Models;

/**
 * App\Models\FundTopUp
 *
 * @property int $id
 * @property int|null $fund_id
 * @property string $code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund|null $fund
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundTopUpTransaction[] $transactions
 * @property-read int|null $transactions_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundTopUp newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundTopUp newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundTopUp query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundTopUp whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundTopUp whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundTopUp whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundTopUp whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundTopUp whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundTopUp extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fund_id', 'amount', 'bunq_transaction_id', 'code', 'state'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fund() {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions() {
        return $this->hasMany(FundTopUpTransaction::class);
    }

    /**
     * Generate new top up code
     * @return string
     */
    public static function generateCode() {
        do {
            $code = strtoupper(
                app()->make('token_generator')->generate(4,2)
            );
        } while(FundTopUp::query()->where('code', $code)->count() > 0);

        return $code;
    }
}
