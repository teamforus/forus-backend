<?php

namespace App\Notifications\Identities;

use App\Notifications\BaseNotification;
use Illuminate\Database\Eloquent\Model;

abstract class BaseIdentityNotification extends BaseNotification
{
    protected static ?string $scope = self::SCOPE_WEBSHOP;

    /**
     * @param Model $loggable
     *
     * @return null[]
     *
     * @throws \Exception
     *
     * @psalm-return array{organization_id: null}
     */
    public static function getMeta($loggable): array
    {
        return [
            'organization_id' => null,
        ];
    }
}
