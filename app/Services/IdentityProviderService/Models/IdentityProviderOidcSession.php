<?php

namespace App\Services\IdentityProviderService\Models;

use App\Models\Implementation;
use App\Services\IdentityProviderService\Queries\IdentityProviderOidcSessionQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Random\RandomException;

/**
 * @property int $id
 * @property string $uid
 * @property int|null $connection_id
 * @property int|null $membership_id
 * @property int|null $implementation_id
 * @property int|null $identity_id
 * @property string $mode
 * @property string $status
 * @property string $state
 * @property string $nonce
 * @property string $code_verifier
 * @property string $authorization_url
 * @property string $final_url
 * @property string|null $target
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $identity_proxy_id
 * @property string|null $exchange_token_hash
 * @property string|null $browser_token_hash
 * @property array|null $verified_account
 * @property \Illuminate\Support\Carbon|null $exchange_expires_at
 * @property \Illuminate\Support\Carbon|null $exchange_consumed_at
 * @property-read \App\Services\IdentityProviderService\Models\IdentityProviderConnection|null $connection
 * @property-read Implementation|null $implementation
 * @property-read \App\Services\IdentityProviderService\Models\IdentityProviderMembership|null $membership
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderOidcSession newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderOidcSession newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderOidcSession query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderOidcSession whereAuthorizationUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderOidcSession whereCodeVerifier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderOidcSession whereConnectionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderOidcSession whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderOidcSession whereExchangeConsumedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderOidcSession whereExchangeExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderOidcSession whereExchangeTokenHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderOidcSession whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderOidcSession whereFinalUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderOidcSession whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderOidcSession whereIdentityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderOidcSession whereIdentityProxyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderOidcSession whereImplementationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderOidcSession whereMembershipId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderOidcSession whereMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderOidcSession whereNonce($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderOidcSession whereResolvedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderOidcSession whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderOidcSession whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderOidcSession whereTarget($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderOidcSession whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderOidcSession whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class IdentityProviderOidcSession extends Model
{
    public const string MODE_DASHBOARD = 'dashboard';
    public const string MODE_WEBSHOP = 'webshop';
    public const string MODE_SELF_LINK = 'self_link';

    public const string STATUS_PENDING = 'pending';
    public const string STATUS_RESOLVED = 'resolved';
    public const string STATUS_ERROR = 'error';
    public const string STATUS_EXPIRED = 'expired';

    protected $table = 'identity_provider_oidc_sessions';

    protected $fillable = [
        'uid', 'connection_id', 'membership_id', 'implementation_id', 'identity_id', 'identity_proxy_id',
        'mode', 'status', 'state', 'nonce', 'code_verifier', 'authorization_url', 'final_url', 'target',
        'exchange_token_hash', 'browser_token_hash', 'exchange_expires_at', 'exchange_consumed_at',
        'expires_at', 'resolved_at', 'verified_account',
    ];

    protected $casts = [
        'nonce' => 'encrypted',
        'code_verifier' => 'encrypted',
        'authorization_url' => 'encrypted',
        'final_url' => 'encrypted',
        'exchange_expires_at' => 'datetime',
        'exchange_consumed_at' => 'datetime',
        'expires_at' => 'datetime',
        'resolved_at' => 'datetime',
        'verified_account' => 'encrypted:array',
    ];

    protected $hidden = [
        'state', 'nonce', 'code_verifier', 'authorization_url',
        'exchange_token_hash', 'browser_token_hash', 'verified_account',
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
    public function membership(): BelongsTo
    {
        return $this->belongsTo(IdentityProviderMembership::class, 'membership_id');
    }

    /**
     * @return BelongsTo
     */
    public function implementation(): BelongsTo
    {
        return $this->belongsTo(Implementation::class);
    }

    /**
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * @return bool
     */
    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    /**
     * @param string $exchangeToken
     * @param string $browserToken
     * @return bool
     */
    public function canExchange(string $exchangeToken, string $browserToken): bool
    {
        return $this->isResolved() &&
            $this->exchange_token_hash &&
            $this->browser_token_hash &&
            hash_equals($this->exchange_token_hash, hash('sha256', $exchangeToken)) &&
            hash_equals($this->browser_token_hash, hash('sha256', $browserToken)) &&
            !$this->exchange_consumed_at &&
            $this->exchange_expires_at &&
            !$this->exchange_expires_at->isPast();
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
            IdentityProviderOidcSessionQuery::wherePending(self::whereKey($this->id))
                ->update([
                    'status' => self::STATUS_EXPIRED,
                    'resolved_at' => now(),
                ]);
        }
    }

    /**
     * @throws RandomException
     * @return string
     */
    public function createExchangeToken(): string
    {
        $exchangeToken = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');

        $this->update([
            'exchange_token_hash' => hash('sha256', $exchangeToken),
            'exchange_expires_at' => now()->addSeconds((int) Config::get('identity_providers.exchange_seconds', 60)),
        ]);

        return $exchangeToken;
    }

    /**
     * @return void
     */
    protected static function booted(): void
    {
        static::creating(function (IdentityProviderOidcSession $session): void {
            $session->uid ??= Str::uuid()->toString();
        });
    }
}
