<?php

namespace App\Services\Forus\Session\Models;

use App\Services\Forus\Session\Services\GeoIp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Services\Forus\Session\Models\Session
 *
 * @property int $id
 * @property string $uid
 * @property string|null $identity_address
 * @property int|null $identity_proxy_id
 * @property \Illuminate\Support\Carbon $last_activity_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Services\Forus\Session\Models\SessionRequest|null $first_request
 * @property-read \App\Services\Forus\Session\Models\SessionRequest|null $last_request
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\Forus\Session\Models\SessionRequest[] $requests
 * @property-read int|null $requests_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\Session newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\Session newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Services\Forus\Session\Models\Session onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\Session query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\Session whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\Session whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\Session whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\Session whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\Session whereIdentityProxyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\Session whereLastActivityAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\Session whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\Session whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Services\Forus\Session\Models\Session withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Services\Forus\Session\Models\Session withoutTrashed()
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
        'uid', 'identity_address', 'identity_proxy_id', 'last_activity_at'
    ];

    protected $dates = [
        'last_activity_at'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function requests(): HasMany
    {
        return $this->hasMany(SessionRequest::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function first_request(): HasOne
    {
        return $this->hasOne(SessionRequest::class)->orderBy('created_at');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function last_request(): HasOne
    {
        return $this->hasOne(SessionRequest::class)->orderByDesc('created_at');
    }

    /**
     * @throws \Exception
     */
    public function terminate()
    {
        $identity_address = $this->identity_address;
        $identity_proxy_id = $this->identity_proxy_id;

        $identityRepo = resolve('forus.services.identity');
        $identityRepo->destroyProxyIdentity($identity_proxy_id, true);

        self::where(compact('identity_address', 'identity_proxy_id'))->delete();
    }

    /**
     * @param $identity_address
     * @throws \Exception
     */
    public static function terminateAll($identity_address)
    {
        while ($session = self::where(compact('identity_address'))->first()) {
            /** @var \App\Services\Forus\Session\Models\Session $session */
            $session->terminate();
        }
    }

    /**
     * @return \Illuminate\Support\Collection
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
}
