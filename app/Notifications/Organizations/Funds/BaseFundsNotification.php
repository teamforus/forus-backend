<?php

namespace App\Notifications\Organizations\Funds;

use App\Models\Organization;
use App\Notifications\Organizations\BaseOrganizationNotification;

abstract class BaseFundsNotification extends BaseOrganizationNotification
{
    protected static ?string $scope = self::SCOPE_SPONSOR;

    /**
     * @param \App\Models\Fund $loggable
     * @return \App\Models\Organization
     */
    public static function getOrganization(mixed $loggable): Organization
    {
        return $loggable->organization;
    }
}
