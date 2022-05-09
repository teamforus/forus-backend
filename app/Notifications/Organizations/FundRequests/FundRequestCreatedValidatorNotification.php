<?php

namespace App\Notifications\Organizations\FundRequests;

/**
 * Notify sponsor/validator about new fund request
 */
class FundRequestCreatedValidatorNotification extends BaseFundsRequestsNotification
{
    protected static ?string $key = 'notifications_fund_requests.created_validator_employee';
    protected static $permissions = 'validate_records';
}
