<?php

namespace App\Notifications\Identities;

use App\Notifications\BaseNotification;
use Illuminate\Database\Eloquent\Model;

/**
 * Class BaseIdentityNotification
 * @package App\Notifications\Identities
 */
abstract class BaseIdentityNotification extends BaseNotification
{
    protected static $scope = self::SCOPE_WEBSHOP;

    /**
     * @param Model $loggable
     * @return array
     * @throws \Exception
     */
    public static function getMeta($loggable): array
    {
        return [
            'organization_id' => null,
        ];
    }
}
