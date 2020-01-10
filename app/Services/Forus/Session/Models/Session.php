<?php

namespace App\Services\Forus\Session\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Services\Forus\Session\Models\Session
 *
 * @property int $id
 * @property string|null $hash
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\Forus\Session\Models\SessionRequest[] $requests
 * @property-read int|null $requests_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\Session newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\Session newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\Session query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\Session whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\Session whereHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\Session whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Session\Models\Session whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Session extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'hash'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function requests()
    {
        return $this->hasMany(SessionRequest::class);
    }
}
