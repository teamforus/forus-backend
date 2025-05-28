<?php

namespace App\Models;

use App\Services\BackofficeApiService\BackofficeApi;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\FundBackofficeLog.
 *
 * @property int $id
 * @property int|null $fund_id
 * @property string|null $identity_address
 * @property string|null $bsn
 * @property int|null $voucher_id
 * @property int|null $voucher_relation_id
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
 * @property-read \App\Models\VoucherRelation|null $voucher_relation
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundBackofficeLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundBackofficeLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundBackofficeLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundBackofficeLog whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundBackofficeLog whereAttempts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundBackofficeLog whereBsn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundBackofficeLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundBackofficeLog whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundBackofficeLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundBackofficeLog whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundBackofficeLog whereLastAttemptAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundBackofficeLog whereRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundBackofficeLog whereResponseBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundBackofficeLog whereResponseCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundBackofficeLog whereResponseError($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundBackofficeLog whereResponseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundBackofficeLog whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundBackofficeLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundBackofficeLog whereVoucherId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundBackofficeLog whereVoucherRelationId($value)
 * @mixin \Eloquent
 */
class FundBackofficeLog extends BaseModel
{
    protected $fillable = [
        'fund_id', 'identity_address', 'bsn', 'action', 'state', 'request_id', 'response_id',
        'response_code', 'response_body', 'response_error', 'attempts', 'last_attempt_at',
        'voucher_id', 'voucher_relation_id',
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
     * @return BelongsTo
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    /**
     * @return BelongsTo
     */
    public function voucher_relation(): BelongsTo
    {
        return $this->belongsTo(VoucherRelation::class);
    }

    /**
     * @return FundBackofficeLog
     */
    public function increaseAttempts(): FundBackofficeLog
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
