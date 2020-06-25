<?php

namespace App\Notifications\Organizations;

use App\Models\Organization;
use App\Notifications\BaseNotification;
use App\Services\Forus\Identity\Models\Identity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Support\Collection;

abstract class BaseOrganizationNotification extends BaseNotification
{
    protected $scope;
    protected $organization_id;
    protected $sendMail = false;

    /**
     * Permissions required to get the notification
     *
     * @var array
     */
    protected static $permissions = [];

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
     * Get the permissions required for the identity to receive the notification
     *
     * @return array
     * @throws \Exception
     */
    public static function getPermissions(): array
    {
        if (empty(static::$permissions) || !is_array(static::$permissions)) {
            throw new \LogicException(sprintf(
                'Permissions list is required for "%s"!',
                static::class
            ));
        }

        return static::$permissions;
    }

    /**
     * Get identities which are eligible for the notification
     *
     * @param Model $loggable
     * @return Collection
     * @throws \Exception
     */
    public static function eligibleIdentities($loggable): Collection
    {
        $employeeAddresses = static::getOrganization($loggable)->employeesWithPermissions(
            self::getPermissions()
        )->pluck('identity_address')->toArray();

        return Identity::whereIn('address', $employeeAddresses)->get();
    }

    /**
     * @param Model $loggable
     * @return array
     * @throws \Exception
     */
    public static function getMeta($loggable): array
    {
        return [
            'organization_id' => static::getOrganization($loggable)->id
        ];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param Identity $identity
     * @return void
     */
    public function toMail(Identity $identity) {}

    /**
     * Get the organization owner of the resource,
     * the permissions will be checked against this organization
     *
     * @param Model $loggable
     * @return Organization
     * @throws \Exception
     */
    abstract public static function getOrganization($loggable): Organization;
}
