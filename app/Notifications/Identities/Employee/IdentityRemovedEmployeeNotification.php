<?php

namespace App\Notifications\Identities\Employee;

class IdentityRemovedEmployeeNotification extends BaseIdentityEmployeeNotification
{
    protected static $key = 'notifications_identities.removed_employee';
    protected static $pushKey = 'employee.deleted';
    protected static $sendPush = true;
}
