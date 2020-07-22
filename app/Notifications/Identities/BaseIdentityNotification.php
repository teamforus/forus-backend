<?php

namespace App\Notifications\Identities;

use App\Notifications\BaseNotification;
use App\Services\Forus\Identity\Models\Identity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class BaseIdentityNotification extends BaseNotification
{
    protected $scope = self::SCOPE_WEBSHOP;

    /**
     * @param Model $loggable
     * @return array
     * @throws \Exception
     */
    public static function getMeta($loggable): array
    {
        return [
            'organization_id' => null
        ];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param Identity $identity
     * @return void
     */
    public function toMail(Identity $identity): void {}
}
