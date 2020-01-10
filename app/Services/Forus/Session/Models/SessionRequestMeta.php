<?php

namespace App\Services\Forus\Session\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Services\Forus\Session\Models\SessionRequestMeta
 *
 * @property int $id
 * @property int $session_request_id
 * @property string $key
 * @property string|null $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Services\Forus\Session\Models\SessionRequest $session_request
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\SessionRequestMeta newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\SessionRequestMeta newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\SessionRequestMeta query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\SessionRequestMeta whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\SessionRequestMeta whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\SessionRequestMeta whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\SessionRequestMeta whereSessionRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\SessionRequestMeta whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\SessionRequestMeta whereValue($value)
 * @mixin \Eloquent
 */
class SessionRequestMeta extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'session_request_id', 'key', 'value'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function session_request() {
        return $this->belongsTo(SessionRequest::class);
    }
}
