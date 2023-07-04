<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Identity2FACode
 *
 * @property int $id
 * @property string $code
 * @property \Illuminate\Support\Carbon $identity_2fa_uuid
 * @property string|null $expire_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Identity2FA|null $identity_2fa
 * @method static \Illuminate\Database\Eloquent\Builder|Identity2FACode newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Identity2FACode newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Identity2FACode query()
 * @method static \Illuminate\Database\Eloquent\Builder|Identity2FACode whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Identity2FACode whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Identity2FACode whereExpireAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Identity2FACode whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Identity2FACode whereIdentity2faUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Identity2FACode whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Identity2FACode extends Model
{
    use SoftDeletes;

    protected $table = 'identity_2fa_codes';

    /**
     * @var string[]
     */
    protected $fillable = [
        'code', 'expire_at', 'identity_2fa_uuid',
    ];

    /**
     * @var string[]
     */
    protected $dates = [
        'expire_at'
    ];

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function identity_2fa(): BelongsTo
    {
        return $this->belongsTo(Identity2FA::class, 'identity_2fa_uid', 'uuid');
    }
}
