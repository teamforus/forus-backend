<?php

namespace App\Notifications\Organizations\Funds;

use App\Models\Organization;
use App\Notifications\Organizations\BaseOrganizationNotification;

/**
 * Class BaseFundsNotification
 * @package App\Notifications\Organizations\Funds
 */
abstract class BaseFundsNotification extends BaseOrganizationNotification
{
    /**
     * @var string
     */
    protected $scope = self::SCOPE_SPONSOR;

    /**
     * @param \App\Models\Fund $loggable
     * @return \App\Models\Organization
     */
    public static function getOrganization($loggable): Organization
    {
        return $loggable->organization;
    }
}
