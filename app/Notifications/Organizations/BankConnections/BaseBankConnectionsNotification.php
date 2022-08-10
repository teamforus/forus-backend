<?php

namespace App\Notifications\Organizations\BankConnections;

use App\Models\Organization;
use App\Notifications\Organizations\BaseOrganizationNotification;

abstract class BaseBankConnectionsNotification extends BaseOrganizationNotification
{
    protected static ?string $scope = self::SCOPE_SPONSOR;

    /**
     * @param \App\Models\BankConnection $loggable
     * @return \App\Models\Organization
     */
    public static function getOrganization(mixed $loggable): Organization
    {
        return $loggable->organization;
    }
}
