<?php

namespace App\Notifications\Organizations\FundRequests;

use App\Models\FundRequest;
use App\Models\Organization;
use App\Notifications\Organizations\BaseOrganizationNotification;

/**
 * Class BaseFundsRequestsNotification
 * @package App\Notifications\FundRequests
 */
abstract class BaseFundsRequestsNotification extends BaseOrganizationNotification
{
    /**
     * @var string
     */
    protected $scope = self::SCOPE_VALIDATOR;

    /**
     * @param \Illuminate\Database\Eloquent\Model|FundRequest $loggable
     * @return \App\Models\Organization|void
     */
    public static function getOrganization($loggable): Organization
    {
        return $loggable->fund->organization;
    }
}
