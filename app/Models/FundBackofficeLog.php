<?php

namespace App\Models;

use App\Services\BackofficeApiService\BackofficeApi;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * App\Models\FundBackofficeLog
 *
 * @property int $id
 * @property int|null $fund_id
 * @property string|null $identity_address
 * @property string|null $bsn
 * @property int|null $voucher_id
 * @property string $action
 * @property string $state
 * @property string|null $request_id
 * @property string|null $response_id
 * @property string|null $response_code
 * @property array|null $response_body
 * @property string|null $response_error
 * @property int $attempts
 * @property string|null $last_attempt_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund|null $fund
 * @property-read \App\Models\Voucher|null $voucher
 * @method static \Illuminate\Database\Eloquent\Builder|FundBackofficeLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundBackofficeLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundBackofficeLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|FundBackofficeLog whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundBackofficeLog whereAttempts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundBackofficeLog whereBsn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundBackofficeLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundBackofficeLog whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundBackofficeLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundBackofficeLog whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundBackofficeLog whereLastAttemptAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundBackofficeLog whereRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundBackofficeLog whereResponseBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundBackofficeLog whereResponseCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundBackofficeLog whereResponseError($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundBackofficeLog whereResponseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundBackofficeLog whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundBackofficeLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundBackofficeLog whereVoucherId($value)
 * @mixin \Eloquent
 */
class FundBackofficeLog extends Model
{
    protected $fillable = [
        'fund_id', 'identity_address', 'bsn', 'action', 'state',
        'response_id', 'response_code', 'response_body', 'response_error',
        'attempts', 'last_attempt_at',
    ];

    protected $casts = [
        'response_body' => 'array',
    ];

    /**
     * @return BelongsTo
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return HasOne
     */
    public function voucher(): HasOne
    {
        return $this->hasOne(Voucher::class);
    }

    /**
     * @return FundBackofficeLog|bool
     */
    public function increaseAttempts()
    {
        return $this->updateModel([
            'attempts' => ++$this->attempts,
            'last_attempt_at' => now(),
        ]);
    }

    /**
     * @return bool
     */
    public function success(): bool
    {
        return $this->state === BackofficeApi::STATE_SUCCESS;
    }
}
