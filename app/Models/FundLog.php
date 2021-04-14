<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\FundLog
 *
 * @property-read \App\Models\Fund $fund
 * @method static \Illuminate\Database\Eloquent\Builder|FundLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundLog query()
 * @mixin \Eloquent
 */
class FundLog extends Model
{
    protected $fillable = [
        'fund_id', 'identity_address', 'identity_bsn', 'action', 'state',
        'error_code', 'error_message','attempts', 'last_attempt_at',
        'response_id'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fund(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }
}
