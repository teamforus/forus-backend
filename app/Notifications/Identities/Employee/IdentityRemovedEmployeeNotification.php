<?php

namespace App\Notifications\Identities\Employee;

/**
 * Notify identity about them being removed from an organization
 */
class IdentityRemovedEmployeeNotification extends BaseIdentityEmployeeNotification
{
    protected static $key = 'notifications_identities.removed_employee';
    protected static $pushKey = 'employee.deleted';
}
