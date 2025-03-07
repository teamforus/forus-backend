<?php

namespace App\Notifications\Organizations;

use App\Models\Identity;
use App\Models\Organization;
use App\Notifications\BaseNotification;
use App\Services\EventLogService\Models\EventLog;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use LogicException;

abstract class BaseOrganizationNotification extends BaseNotification
{
    protected static ?string $scope;

    /**
     * Permissions required to get the notification.
     *
     * @var array|string
     */
    protected static string|array $permissions = [];

    /**
     * Get the permissions required for the identity to receive the notification.
     *
     * @throws Exception
     * @return array
     */
    public static function getPermissions(): array
    {
        if (empty(static::$permissions) || (
            !is_array(static::$permissions) && !is_string(static::$permissions)
        )) {
            throw new LogicException(sprintf(
                'Permissions list is required for "%s"!',
                static::class
            ));
        }

        return (array) static::$permissions;
    }

    /**
     * Get identities which are eligible for the notification.
     *
     * @param Model $loggable
     * @param EventLog $eventLog
     * @throws Exception
     * @return Collection
     */
    public static function eligibleIdentities($loggable, EventLog $eventLog): Collection
    {
        $employeeAddresses = static::getOrganization($loggable)->employeesWithPermissionsQuery(
            self::getPermissions()
        )->select('employees.identity_address')->distinct()->getQuery();

        return Identity::whereIn('address', $employeeAddresses)->get();
    }

    /**
     * @param Model $loggable
     * @throws Exception
     * @return array
     */
    public static function getMeta($loggable): array
    {
        return [
            'organization_id' => static::getOrganization($loggable)->id,
        ];
    }

    /**
     * Get the organization owner of the resource,
     * the permissions will be checked against this organization.
     *
     * @param Model $loggable
     * @throws Exception
     * @return Organization
     */
    abstract public static function getOrganization(mixed $loggable): Organization;
}
