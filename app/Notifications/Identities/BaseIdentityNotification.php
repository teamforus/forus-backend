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
     * Get identities which are eligible for the notification
     *
     * @param Identity $loggable
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public static function eligibleIdentities($loggable): Collection
    {
        return collect($loggable);
    }

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
     * @param mixed $notifiable
     * @return array|string[]
     */
    public function via($notifiable): array
    {
        return ['database'];
    }
}
