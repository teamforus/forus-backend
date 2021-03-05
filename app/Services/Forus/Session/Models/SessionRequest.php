<?php

namespace App\Services\Forus\Session\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Services\Forus\Session\Models\SessionRequest
 *
 * @property int $id
 * @property int $session_id
 * @property string $ip
 * @property string|null $client_type
 * @property string|null $client_version
 * @property string|null $endpoint
 * @property string|null $method
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Services\Forus\Session\Models\Session $session
 * @method static \Illuminate\Database\Eloquent\Builder|SessionRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SessionRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SessionRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder|SessionRequest whereClientType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SessionRequest whereClientVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SessionRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SessionRequest whereEndpoint($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SessionRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SessionRequest whereIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SessionRequest whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SessionRequest whereSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SessionRequest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SessionRequest whereUserAgent($value)
 * @mixin \Eloquent
 */
class SessionRequest extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'session_id', 'endpoint', 'method', 'user_agent', 'ip',
        'client_type', 'client_version',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function session()
    {
        return $this->belongsTo(Session::class);
    }
}
