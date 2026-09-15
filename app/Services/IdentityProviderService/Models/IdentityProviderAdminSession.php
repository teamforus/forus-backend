<?php

namespace App\Services\IdentityProviderService\Models;

use App\Models\Identity;
use App\Models\Organization;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderErrorCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uid
 * @property int $organization_id
 * @property int|null $connection_id
 * @property int $requested_by_identity_id
 * @property string $final_url
 * @property string $status
 * @property string $oidc_state_hash
 * @property string $oidc_nonce
 * @property string $oidc_code_verifier
 * @property string $oidc_authorization_url
 * @property string|null $consent_state_hash
 * @property string|null $expected_tenant_id
 * @property string|null $admin_object_id
 * @property string|null $error_code
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $tenant_verified_at
 * @property \Illuminate\Support\Carbon|null $consented_at
 * @property \Illuminate\Support\Carbon|null $confirmed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $previous_connection_id
 * @property-read Organization $organization
 * @property-read Identity $requested_by
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderAdminSession newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderAdminSession newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderAdminSession query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderAdminSession whereAdminObjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderAdminSession whereConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderAdminSession whereConnectionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderAdminSession whereConsentStateHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderAdminSession whereConsentedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderAdminSession whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderAdminSession whereErrorCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderAdminSession whereExpectedTenantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderAdminSession whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderAdminSession whereFinalUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderAdminSession whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderAdminSession whereOidcAuthorizationUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderAdminSession whereOidcCodeVerifier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderAdminSession whereOidcNonce($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderAdminSession whereOidcStateHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderAdminSession whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderAdminSession whereRequestedByIdentityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderAdminSession wherePreviousConnectionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderAdminSession whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderAdminSession whereTenantVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderAdminSession whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderAdminSession whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class IdentityProviderAdminSession extends Model
{
    public const string STATUS_VERIFYING_TENANT = 'verifying_tenant';
    public const string STATUS_AWAITING_CONSENT = 'awaiting_consent';
    public const string STATUS_CONFIRMED = 'confirmed';
    public const string STATUS_ERROR = 'error';
    public const string STATUS_EXPIRED = 'expired';

    protected $table = 'identity_provider_admin_sessions';

    protected $fillable = [
        'uid', 'organization_id', 'connection_id', 'previous_connection_id', 'requested_by_identity_id',
        'status', 'final_url', 'oidc_state_hash', 'oidc_nonce', 'oidc_code_verifier',
        'oidc_authorization_url', 'consent_state_hash', 'expected_tenant_id', 'admin_object_id',
        'error_code', 'expires_at', 'tenant_verified_at', 'consented_at', 'confirmed_at',
    ];

    protected $hidden = [
        'oidc_state_hash', 'oidc_nonce', 'oidc_code_verifier', 'consent_state_hash',
    ];

    protected $casts = [
        'oidc_nonce' => 'encrypted',
        'oidc_code_verifier' => 'encrypted',
        'oidc_authorization_url' => 'encrypted',
        'expires_at' => 'datetime',
        'tenant_verified_at' => 'datetime',
        'consented_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo
     */
    public function requested_by(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'requested_by_identity_id');
    }

    /**
     * @return bool
     */
    public function isVerifyingTenant(): bool
    {
        return $this->status === self::STATUS_VERIFYING_TENANT;
    }

    /**
     * @return bool
     */
    public function isAwaitingConsent(): bool
    {
        return $this->status === self::STATUS_AWAITING_CONSENT;
    }

    /**
     * @return bool
     */
    public function isExpired(): bool
    {
        return !$this->expires_at || $this->expires_at->isPast();
    }

    /**
     * @return void
     */
    public function markExpired(): void
    {
        if ($this->isExpired()) {
            self::whereKey($this->id)
                ->where('status', $this->status)
                ->where('expires_at', '<=', now())
                ->update([
                    'status' => self::STATUS_EXPIRED,
                    'error_code' => IdentityProviderErrorCode::SESSION_EXPIRED,
                ]);
        }
    }

    /**
     * @return void
     */
    protected static function booted(): void
    {
        static::creating(function (IdentityProviderAdminSession $session): void {
            $session->uid ??= Str::uuid()->toString();
        });
    }
}
