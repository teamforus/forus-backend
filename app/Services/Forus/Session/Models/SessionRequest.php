<?php

namespace App\Services\Forus\Session\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Services\Forus\Session\Models\SessionRequest
 *
 * @property int $id
 * @property int $session_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\Forus\Session\Models\SessionRequestMeta[] $metas
 * @property-read int|null $metas_count
 * @property-read \App\Services\Forus\Session\Models\Session $session
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\SessionRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\SessionRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\SessionRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\SessionRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\SessionRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\SessionRequest whereSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\SessionRequest whereUpdatedAt($value)
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
        'session_id'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function metas()
    {
        return $this->hasMany(SessionRequestMeta::class);
    }
}
