<?php

namespace App\Services\MollieService\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Services\MollieService\Models\MollieConnectionToken
 *
 * @property int $id
 * @property string $access_token
 * @property string $remember_token
 * @property \Illuminate\Support\Carbon $expired_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int $mollie_connection_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Services\MollieService\Models\MollieConnection $mollie_connection
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnectionToken newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnectionToken newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnectionToken onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnectionToken query()
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnectionToken whereAccessToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnectionToken whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnectionToken whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnectionToken whereExpiredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnectionToken whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnectionToken whereMollieConnectionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnectionToken whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnectionToken whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnectionToken withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnectionToken withoutTrashed()
 * @mixin \Eloquent
 */
class MollieConnectionToken extends Model
{
    use SoftDeletes;

    /**
     * @var string[]
     */
    protected $fillable = [
        'access_token', 'remember_token', 'expired_at', 'mollie_connection_id',
    ];

    /**
     * @var string[]
     */
    protected $hidden = [
        'access_token',
        'remember_token',
    ];

    /**
     * @var string[]
     */
    protected $dates = [
        'expired_at',
    ];

    /**
     * @noinspection PhpUnused
     * @return BelongsTo
     */
    public function mollie_connection(): BelongsTo
    {
        return $this->belongsTo(MollieConnection::class);
    }

    /**
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expired_at->isPast();
    }
}
