<?php

namespace App\Notifications\Identities\Employee;

/**
 * Notify identity about their permissions being adjusted
 */
class IdentityChangedEmployeeRolesNotification extends BaseIdentityEmployeeNotification
{
    protected static $key = 'notifications_identities.changed_employee_roles';
}
