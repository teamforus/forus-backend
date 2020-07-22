<?php

namespace App\Notifications\Identities\Employee;

use App\Models\Employee;
use App\Notifications\Identities\BaseIdentityNotification;
use App\Services\Forus\Identity\Models\Identity;
use Illuminate\Support\Collection;

abstract class BaseIdentityEmployeeNotification extends BaseIdentityNotification
{
    /**
     * Get identities which are eligible for the notification
     *
     * @param Employee $loggable
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public static function eligibleIdentities($loggable): Collection
    {
        return Identity::whereAddress($loggable->identity_address)->get();
    }
}
