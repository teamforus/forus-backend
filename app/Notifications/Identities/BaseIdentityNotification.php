<?php

namespace App\Notifications\Identities;

use App\Notifications\BaseNotification;
use Exception;
use Illuminate\Database\Eloquent\Model;

abstract class BaseIdentityNotification extends BaseNotification
{
    protected static ?string $scope = self::SCOPE_WEBSHOP;

    /**
     * @param Model $loggable
     * @throws Exception
     * @return array
     */
    public static function getMeta($loggable): array
    {
        return [
            'organization_id' => null,
        ];
    }
}
