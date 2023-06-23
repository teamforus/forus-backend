<?php

namespace App\Models;

use App\Services\Forus\Session\Models\Session;
use App\Services\Forus\Session\Models\SessionRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * App\Models\IdentityProxy
 *
 * @property int $id
 * @property string $type
 * @property string|null $identity_address
 * @property string|null $access_token
 * @property string $exchange_token
 * @property string $state
 * @property string|null $identity_2fa_uuid
 * @property string|null $identity_2fa_code
 * @property int|null $identity_2fa_parent_proxy_id
 * @property Carbon|null $activated_at
 * @property Carbon|null $deleted_at
 * @property int $expires_in
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read bool $exchange_time_expired
 * @property-read \App\Models\Identity|null $identity
 * @property-read \App\Models\Identity2FA|null $identity_2fa
 * @property-read IdentityProxy|null $identity_2fa_parent_proxy
 * @property-read \Illuminate\Database\Eloquent\Collection|Session[] $sessions
 * @property-read int|null $sessions_count
 * @property-read \Illuminate\Database\Eloquent\Collection|Session[] $sessions_with_trashed
 * @property-read int|null $sessions_with_trashed_count
 * @method static Builder|IdentityProxy newModelQuery()
 * @method static Builder|IdentityProxy newQuery()
 * @method static Builder|IdentityProxy onlyTrashed()
 * @method static Builder|IdentityProxy query()
 * @method static Builder|IdentityProxy whereAccessToken($value)
 * @method static Builder|IdentityProxy whereActivatedAt($value)
 * @method static Builder|IdentityProxy whereCreatedAt($value)
 * @method static Builder|IdentityProxy whereDeletedAt($value)
 * @method static Builder|IdentityProxy whereExchangeToken($value)
 * @method static Builder|IdentityProxy whereExpiresIn($value)
 * @method static Builder|IdentityProxy whereId($value)
 * @method static Builder|IdentityProxy whereIdentity2faCode($value)
 * @method static Builder|IdentityProxy whereIdentity2faParentProxyId($value)
 * @method static Builder|IdentityProxy whereIdentity2faUuid($value)
 * @method static Builder|IdentityProxy whereIdentityAddress($value)
 * @method static Builder|IdentityProxy whereState($value)
 * @method static Builder|IdentityProxy whereType($value)
 * @method static Builder|IdentityProxy whereUpdatedAt($value)
 * @method static Builder|IdentityProxy withTrashed()
 * @method static Builder|IdentityProxy withoutTrashed()
 * @mixin \Eloquent
 */
class IdentityProxy extends Model
{
    use SoftDeletes;

    public const STATE_ACTIVE = 'active';
    public const STATE_PENDING = 'pending';

    // User logged out
    public const STATE_DESTROYED = 'destroyed';

    // Session terminated by the user
    public const STATE_TERMINATED = 'terminated';

    // Session expired
    public const STATE_EXPIRED = 'expired';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'identity_address', 'access_token', 'exchange_token', 'state', 'type',
        'expires_in', 'deleted_at', 'termination_reason', 'activated_at',
    ];

    /**
     * @var string[]
     */
    protected $dates = [
        'activated_at',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'identity_address', 'address');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function identity_2fa_parent_proxy(): BelongsTo
    {
        return $this->belongsTo(IdentityProxy::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function identity_2fa(): BelongsTo
    {
        return $this->belongsTo(Identity2FA::class);
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function sessions_with_trashed(): HasMany
    {
        /** @var HasMany|SoftDeletes $relation */
        $relation = $this->hasMany(Session::class);

        return $relation->withTrashed();
    }

    /**
     * Activation time expired
     *
     * @return bool
     * @noinspection PhpUnused
     */
    public function getExchangeTimeExpiredAttribute(): bool
    {
        return $this->created_at->addSeconds($this->expires_in)->isPast();
    }

    /**
     * @param string|null $access_token
     * @return static|null
     */
    public static function findByAccessToken(?string $access_token): ?static
    {
        return !empty($access_token) ? self::whereAccessToken($access_token)->first() : null;
    }

    /**
     * @param string $exchange_token
     * @param string $type
     * @return IdentityProxy|null
     */
    public static function findByExchangeToken(string $exchange_token, string $type): ?IdentityProxy
    {
        return IdentityProxy::where(compact('exchange_token', 'type'))->first();
    }

    /**
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->state == static::STATE_PENDING;
    }

    /**
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->state == static::STATE_EXPIRED;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->state == static::STATE_ACTIVE;
    }

    /**
     * @return bool
     */
    public function deactivateByLogout(): bool
    {
        return $this->deactivate(static::STATE_DESTROYED);
    }

    /**
     * @param bool $expired
     * @return IdentityProxy
     * @throws \Throwable
     */
    public function deactivateBySession(bool $expired = true): static
    {
        DB::transaction(function() use ($expired) {
            $state = $expired ? static::STATE_EXPIRED : static::STATE_TERMINATED;

            $this->deactivate($state);
            $this->sessions()->delete();
        });

        return $this;
    }

    /**
     * @param string $state
     * @return bool
     */
    protected function deactivate(string $state): bool
    {
        $this->update([
            'state' => $state,
        ]);

        return (bool) $this->delete();
    }

    /**
     * @return bool
     */
    public function isDeactivated(): bool
    {
        return in_array($this->state, [static::STATE_EXPIRED, static::STATE_TERMINATED]);
    }

    /**
     * @return bool
     */
    public function is2FAConfirmed(): bool
    {
        return $this->identity_2fa()->exists() && $this->identity_2fa_code;
    }

    /**
     * @param string $ip
     * @param int $timeout
     * @return ?IdentityProxy
     */
    public function find2FAInheritableProxy(string $ip, int $timeout): ?IdentityProxy
    {
        $lastRequestQuery = SessionRequest::query()
            ->whereRelation('session', fn (Builder|SessionRequest $q) => $q->whereColumn([
                'sessions.identity_proxy_id' => 'identity_proxies.id',
            ]))
            ->where('ip', $ip)
            ->where('created_at', '>', Carbon::now()->subHours($timeout))
            ->latest('created_at')
            ->select('created_at')
            ->take(1);

        return $this->identity->proxies()
            ->addSelect(['last_request' => $lastRequestQuery])
            ->whereNotNull('identity_2fa_uuid')
            ->whereNotNull('identity_2fa_code')
            ->orderBy('last_request', 'desc')
            ->withTrashed()
            ->first();
    }

    /**
     * @return bool
     */
    public function shouldInherit2FA(): bool
    {
        return $this->identity()
            ->where('auth_2fa_remember_ip', false)
            ->orWhereRelation('funds.fund_config', 'auth_2fa_remember_ip', false)
            ->orWhereRelation('employees.organization', 'auth_2fa_remember_ip', false)
            ->doesntExist();
    }

    /**
     * @param string $ip
     * @param int $timeout
     * @return bool
     */
    public function inherit2FAState(string $ip, int $timeout): bool
    {
        if ($this->shouldInherit2FA() && $proxy = $this->find2FAInheritableProxy($ip, $timeout)) {
            return $this->forceFill([
                'identity_2fa_code' => $proxy->identity_2fa_code,
                'identity_2fa_uuid' => $proxy->identity_2fa_uuid,
                'identity_2fa_parent_proxy_id' => $proxy->id,
            ])->save();
        }

        return false;
    }
}
