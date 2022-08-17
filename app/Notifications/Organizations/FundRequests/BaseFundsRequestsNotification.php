<?php

namespace App\Notifications\Organizations\FundRequests;

use App\Models\FundRequest;
use App\Models\Organization;
use App\Notifications\Organizations\BaseOrganizationNotification;

abstract class BaseFundsRequestsNotification extends BaseOrganizationNotification
{
    protected static ?string $scope = self::SCOPE_VALIDATOR;

    /**
     * @param FundRequest $loggable
     * @return \App\Models\Organization
     */
    public static function getOrganization(mixed $loggable): Organization
    {
        return $loggable->fund->organization;
    }
}
