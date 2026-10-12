<?php

namespace App\Services\IdentityProviderService\Models;

use App\Models\Employee;
use App\Models\Identity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uid
 * @property int $connection_id
 * @property int $external_identity_id
 * @property int|null $identity_id
 * @property int|null $employee_id
 * @property string $claim_state
 * @property string|null $consent_version
 * @property \Illuminate\Support\Carbon|null $consented_at
 * @property \Illuminate\Support\Carbon|null $retired_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Services\IdentityProviderService\Models\IdentityProviderConnection $connection
 * @property-read Employee|null $employee
 * @property-read \App\Services\IdentityProviderService\Models\ExternalIdentity $external_identity
 * @property-read Identity|null $identity
 * @method static Builder<static>|IdentityProviderMembership newModelQuery()
 * @method static Builder<static>|IdentityProviderMembership newQuery()
 * @method static Builder<static>|IdentityProviderMembership query()
 * @method static Builder<static>|IdentityProviderMembership whereClaimState($value)
 * @method static Builder<static>|IdentityProviderMembership whereConnectionId($value)
 * @method static Builder<static>|IdentityProviderMembership whereConsentVersion($value)
 * @method static Builder<static>|IdentityProviderMembership whereConsentedAt($value)
 * @method static Builder<static>|IdentityProviderMembership whereCreatedAt($value)
 * @method static Builder<static>|IdentityProviderMembership whereEmployeeId($value)
 * @method static Builder<static>|IdentityProviderMembership whereExternalIdentityId($value)
 * @method static Builder<static>|IdentityProviderMembership whereId($value)
 * @method static Builder<static>|IdentityProviderMembership whereIdentityId($value)
 * @method static Builder<static>|IdentityProviderMembership whereRetiredAt($value)
 * @method static Builder<static>|IdentityProviderMembership whereUid($value)
 * @method static Builder<static>|IdentityProviderMembership whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class IdentityProviderMembership extends Model
{
    public const string CLAIM_PENDING = 'pending_link';
    public const string CLAIM_CLAIMED = 'claimed';
    public const string CLAIM_RETIRED = 'retired';

    protected $table = 'identity_provider_memberships';

    protected $fillable = [
        'uid', 'connection_id', 'external_identity_id', 'identity_id', 'employee_id',
        'claim_state',
        'consent_version', 'consented_at',
        'retired_at',
    ];

    protected $casts = [
        'consented_at' => 'datetime',
        'retired_at' => 'datetime',
    ];

    /**
     * @return BelongsTo
     */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(IdentityProviderConnection::class, 'connection_id');
    }

    /**
     * @return BelongsTo
     */
    public function external_identity(): BelongsTo
    {
        return $this->belongsTo(ExternalIdentity::class, 'external_identity_id');
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
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return bool
     */
    public function isClaimed(): bool
    {
        return $this->claim_state === self::CLAIM_CLAIMED;
    }

    /**
     * @return bool
     */
    public function isRetired(): bool
    {
        return $this->claim_state === self::CLAIM_RETIRED;
    }

    /**
     * @return void
     */
    protected static function booted(): void
    {
        static::creating(function (IdentityProviderMembership $membership): void {
            $membership->uid ??= Str::uuid()->toString();
        });
    }
}
