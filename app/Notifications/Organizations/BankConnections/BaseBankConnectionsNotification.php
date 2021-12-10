<?php

namespace App\Notifications\Organizations\BankConnections;

use App\Models\Organization;
use App\Notifications\Organizations\BaseOrganizationNotification;

abstract class BaseBankConnectionsNotification extends BaseOrganizationNotification
{
    /**
     * @var string
     */
    protected static $scope = self::SCOPE_SPONSOR;

    /**
     * @param \App\Models\BankConnection $loggable
     * @return \App\Models\Organization
     */
    public static function getOrganization($loggable): Organization
    {
        return $loggable->organization;
    }
}
