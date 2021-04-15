<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\FundLog
 *
 * @property int $id
 * @property int $fund_id
 * @property string $identity_address
 * @property string $identity_bsn
 * @property string $action
 * @property string|null $response_id
 * @property string $state
 * @property string|null $error_code
 * @property string|null $error_message
 * @property int $attempts
 * @property string|null $last_attempt_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $fund
 * @method static \Illuminate\Database\Eloquent\Builder|FundLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|FundLog whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundLog whereAttempts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundLog whereErrorCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundLog whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundLog whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundLog whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundLog whereIdentityBsn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundLog whereLastAttemptAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundLog whereResponseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundLog whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundLog whereUpdatedAt($value)
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
