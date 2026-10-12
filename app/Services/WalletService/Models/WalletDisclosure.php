<?php

namespace App\Services\WalletService\Models;

use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Identity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $wallet_session_id
 * @property int $wallet_flow_id
 * @property int $identity_id
 * @property int $fund_id
 * @property int|null $fund_request_id
 * @property array $payload
 * @property array $records
 * @property Carbon $verified_at
 * @property Carbon $expires_at
 * @property Carbon|null $consumed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string|null $email
 * @property-read WalletSession|null $wallet_session
 * @property-read WalletFlow $wallet_flow
 * @property-read Identity $identity
 * @property-read Fund $fund
 * @property-read FundRequest|null $fund_request
 */
class WalletDisclosure extends Model
{
    protected $fillable = [
        'wallet_session_id', 'wallet_flow_id', 'identity_id', 'fund_id', 'fund_request_id',
        'payload', 'records', 'verified_at', 'expires_at', 'consumed_at',
    ];

    protected $hidden = ['payload', 'records'];

    protected $casts = [
        'payload' => 'encrypted:array',
        'records' => 'encrypted:array',
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    /**
     * @return bool
     */
    public function isUsable(): bool
    {
        return !$this->fund_request_id && !$this->consumed_at && $this->expires_at->isFuture();
    }

    /**
     * @return string|null
     */
    public function getEmailAttribute(): ?string
    {
        $email = Arr::get($this->payload, 'mapping.email.value');

        return is_string($email) && trim($email) !== '' ? trim($email) : null;
    }

    /**
     * @return BelongsTo
     */
    public function wallet_session(): BelongsTo
    {
        return $this->belongsTo(WalletSession::class)->withTrashed();
    }

    /**
     * @return BelongsTo
     */
    public function wallet_flow(): BelongsTo
    {
        return $this->belongsTo(WalletFlow::class)->withTrashed();
    }

    /**
     * @return BelongsTo
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class);
    }

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
    public function fund_request(): BelongsTo
    {
        return $this->belongsTo(FundRequest::class);
    }
}
