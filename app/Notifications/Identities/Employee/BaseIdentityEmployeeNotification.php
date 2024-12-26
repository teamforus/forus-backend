<?php

namespace App\Notifications\Identities\Employee;

use App\Models\Employee;
use App\Models\Identity;
use App\Notifications\Identities\BaseIdentityNotification;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Support\Collection;

abstract class BaseIdentityEmployeeNotification extends BaseIdentityNotification
{
    /**
     * Get identities which are eligible for the notification
     *
     * @param Employee $loggable
     * @param EventLog $eventLog
     * @return \Illuminate\Support\Collection
     */
    public static function eligibleIdentities($loggable, EventLog $eventLog): Collection
    {
        return Identity::whereAddress($loggable->identity_address)->get();
    }
}
