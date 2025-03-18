<?php

namespace App\Notifications\Organizations\FundRequests;

use App\Models\Permission;

/**
 * Notify sponsor/validator about new fund request.
 */
class FundRequestCreatedValidatorNotification extends BaseFundsRequestsNotification
{
    protected static ?string $key = 'notifications_fund_requests.created_validator_employee';
    protected static string|array $permissions = Permission::VALIDATE_RECORDS;
}
