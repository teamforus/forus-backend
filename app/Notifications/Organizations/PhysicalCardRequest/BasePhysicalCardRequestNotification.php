<?php

namespace App\Notifications\Organizations\PhysicalCardRequest;

use App\Models\Organization;
use App\Notifications\Organizations\BaseOrganizationNotification;

/**
 * Class BasePhysicalCardRequestNotification
 */
abstract class BasePhysicalCardRequestNotification extends BaseOrganizationNotification
{
    /**
     * @var string
     */
    protected $scope = self::SCOPE_SPONSOR;

    /**
     * @param \App\Models\PhysicalCardRequest $loggable
     * @return \App\Models\Organization
     */
    public static function getOrganization($loggable): Organization
    {
        return $loggable->voucher->fund->organization;
    }
}