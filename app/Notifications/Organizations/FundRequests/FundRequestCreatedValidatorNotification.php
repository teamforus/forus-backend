<?php

namespace App\Notifications\Organizations\FundRequests;

/**
 * Class FundRequestCreatedValidatorNotification
 * @package App\Notifications\FundRequests
 */
class FundRequestCreatedValidatorNotification extends BaseFundsRequestsNotification
{
    protected $key = 'notifications_fund_requests.created_validator_employee';
    protected static $permissions = [
        'validate_records'
    ];
}
