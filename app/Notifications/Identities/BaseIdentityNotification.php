<?php

namespace App\Notifications\Identities;

use App\Notifications\BaseNotification;
use App\Services\Forus\Identity\Models\Identity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Support\Collection;

abstract class BaseIdentityNotification extends BaseNotification
{
    protected $scope = self::SCOPE_WEBSHOP;
    protected $sendMail = false;

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
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable): array
    {
        return [
            'database',
            ($this->sendMail ?? false) ? MailChannel::class : null,
        ];
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
     * Get the mail representation of the notification.
     *
     * @param Identity $identity
     * @return void
     */
    public function toMail(Identity $identity) {}
}
