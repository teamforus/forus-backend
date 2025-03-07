<?php

namespace App\Notifications\Identities\Reimbursement;

use App\Models\Identity;
use App\Models\Reimbursement;
use App\Notifications\Identities\BaseIdentityNotification;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Support\Collection;

abstract class BaseIdentityReimbursementNotification extends BaseIdentityNotification
{
    /**
     * Get identities which are eligible for the notification.
     *
     * @param Reimbursement $loggable
     * @param EventLog $eventLog
     * @return \Illuminate\Support\Collection
     */
    public static function eligibleIdentities($loggable, EventLog $eventLog): Collection
    {
        return Identity::where('id', $loggable->voucher->identity_id)->get();
    }
}
