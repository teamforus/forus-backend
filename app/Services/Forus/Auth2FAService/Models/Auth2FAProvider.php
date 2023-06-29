<?php

namespace App\Services\Forus\Auth2FAService\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * \App\Services\Forus\Auth2FAService\Models\Auth2FAProvider
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string $type
 * @property string|null $url_ios
 * @property string|null $url_android
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Auth2FAProvider newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Auth2FAProvider newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Auth2FAProvider query()
 * @method static \Illuminate\Database\Eloquent\Builder|Auth2FAProvider whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Auth2FAProvider whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Auth2FAProvider whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Auth2FAProvider whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Auth2FAProvider whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Auth2FAProvider whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Auth2FAProvider whereUrlAndroid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Auth2FAProvider whereUrlIos($value)
 * @mixin \Eloquent
 */
class Auth2FAProvider extends Model
{
    public const TYPE_PHONE = 'phone';
    public const TYPE_AUTHENTICATOR = 'authenticator';

    protected $table = 'auth_2fa_providers';

    /**
     * @return bool
     * @namespace PhpUnused
     */
    public function isTypePhone(): bool
    {
        return $this->type == self::TYPE_PHONE;
    }

    /**
     * @return bool
     * @namespace PhpUnused
     */
    public function isTypeAuthenticator(): bool
    {
        return $this->type == self::TYPE_AUTHENTICATOR;
    }

    /**
     * @param string $key
     * @return Auth2FAProvider|null
     */
    public static function findByKey(string $key): ?Auth2FAProvider
    {
        return self::where('key', $key)->first();
    }
}
