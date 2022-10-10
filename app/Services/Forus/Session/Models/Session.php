<?php

namespace App\Services\Forus\Session\Models;

use App\Models\Identity;
use App\Models\IdentityProxy;
use App\Models\Implementation;
use App\Services\Forus\Session\Services\GeoIp;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;

/**
 * App\Services\Forus\Session\Models\Session
 *
 * @property int $id
 * @property string $uid
 * @property string|null $identity_address
 * @property int|null $identity_proxy_id
 * @property Carbon $last_activity_at
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \App\Services\Forus\Session\Models\SessionRequest|null $first_request
 * @property-read string|null $initial_client_type
 * @property-read Identity|null $identity
 * @property-read IdentityProxy|null $identity_proxy
 * @property-read IdentityProxy|null $identity_proxy_with_trashed
 * @property-read \App\Services\Forus\Session\Models\SessionRequest|null $last_request
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\Forus\Session\Models\SessionRequest[] $requests
 * @property-read int|null $requests_count
 * @method static Builder|Session newModelQuery()
 * @method static Builder|Session newQuery()
 * @method static \Illuminate\Database\Query\Builder|Session onlyTrashed()
 * @method static Builder|Session query()
 * @method static Builder|Session whereCreatedAt($value)
 * @method static Builder|Session whereDeletedAt($value)
 * @method static Builder|Session whereId($value)
 * @method static Builder|Session whereIdentityAddress($value)
 * @method static Builder|Session whereIdentityProxyId($value)
 * @method static Builder|Session whereLastActivityAt($value)
 * @method static Builder|Session whereUid($value)
 * @method static Builder|Session whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|Session withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Session withoutTrashed()
 * @mixin \Eloquent
 */
class Session extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uid', 'identity_address', 'identity_proxy_id', 'last_activity_at',
    ];

    protected $dates = [
        'last_activity_at',
    ];

    /**
     * @return BelongsTo
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class);
    }

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function identity_proxy(): BelongsTo
    {
        return $this->belongsTo(IdentityProxy::class);
    }

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function identity_proxy_with_trashed(): BelongsTo
    {
        return $this->belongsTo(IdentityProxy::class)->where(function(Builder $builder) {
            /** @var Builder|SoftDeletes $builder */
            $builder->withTrashed();
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function requests(): HasMany
    {
        return $this->hasMany(SessionRequest::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     * @noinspection PhpUnused
     */
    public function first_request(): HasOne
    {
        return $this->hasOne(SessionRequest::class)->orderBy('created_at');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     * @noinspection PhpUnused
     */
    public function last_request(): HasOne
    {
        return $this->hasOne(SessionRequest::class)->orderByDesc('created_at');
    }

    /**
     * @param bool $expired
     * @return void
     * @throws \Throwable
     */
    public function terminate(bool $expired = true): void
    {
        $this->identity_proxy->deactivateBySession($expired);
    }

    /**
     * @return \Illuminate\Support\Collection|null
     */
    public function locations(): ?\Illuminate\Support\Collection
    {
        if (GeoIp::isAvailable()) {
            $ipAddresses = $this->requests()->distinct()->pluck('ip');

            return $ipAddresses->map(function($ip) {
                return GeoIp::getLocation($ip);
            });
        }

        return null;
    }

    /**
     * An session is considered active if last request was made no more
     * than 5 minutes ago
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->last_activity_at->diffInMinutes(now()) <= 5;
    }

    /**
     * @return bool
     */
    public function shouldExpire(): bool
    {
        $expireTime = $this->getExpireTime();

        if (!$this->identity_proxy) {
            return true;
        }

        // Token expired
        if (now()->isAfter($this->identity_proxy->created_at->clone()->addYears(4))) {
            return true;
        }

        // Long time no activity
        if ($expireTime && now()->isAfter($expireTime)) {
            return true;
        }

        return false;
    }

    /**
     * @return Carbon|null
     */
    public function getExpireTime(): ?Carbon
    {
        $lastActivityTime = $this->last_activity_at->clone();

        $appTime = Config::get('forus.sessions.app_expire_time');
        $webshopTime = Config::get('forus.sessions.webshop_expire_time');
        $dashboardTime = Config::get('forus.sessions.dashboard_expire_time');

        return match($this->initial_client_type) {
            Implementation::FRONTEND_WEBSHOP => $webshopTime['value'] ? $lastActivityTime->add(
                $webshopTime['unit'] ?? 'minutes', $webshopTime['value'],
            ) : null,
            Implementation::FRONTEND_SPONSOR_DASHBOARD,
            Implementation::FRONTEND_PROVIDER_DASHBOARD,
            Implementation::FRONTEND_VALIDATOR_DASHBOARD => $dashboardTime['value'] ? $lastActivityTime->add(
                $dashboardTime['unit'] ?? 'months', $dashboardTime['value'],
            ) : null,
            default => $appTime['value'] ? $lastActivityTime->add(
                $appTime['unit'] ?? 'years', $appTime['value'],
            ) : null,
        };
    }

    /**
     * @return string|null
     * @noinspection PhpUnused
     */
    public function getInitialClientTypeAttribute(): ?string
    {
        return $this->first_request?->client_type;
    }

    /**
     * @return bool
     */
    public function isTerminated(): bool
    {
        return $this->trashed();
    }
}
