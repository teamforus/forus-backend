<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class FundTopUp
 * @package App\Models
 * @property mixed $id
 * @property integer $fund_id
 * @property float $amount
 * @property integer|null $bunq_transaction_id
 * @property string $code
 * @property string $state
 * @property Fund $fund
 * @property Collection $transactions
 * @property Carbon $created_at
 * @property Carbon $updated_at
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
                app()->make('token_generator')->generate(4,4)
            );
        } while(FundTopUp::query()->where('code', $code)->count() > 0);

        return $code;
    }
}
