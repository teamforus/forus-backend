<?php

namespace App\Notifications\Identities\ProductReservation;

use App\Models\Identity;
use App\Models\ProductReservation;
use App\Notifications\Identities\BaseIdentityNotification;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Support\Collection;

abstract class BaseProductReservationNotification extends BaseIdentityNotification
{
    /**
     * Get identities which are eligible for the notification.
     *
     * @param ProductReservation $loggable
     * @param EventLog $eventLog
     * @return \Illuminate\Support\Collection
     */
    public static function eligibleIdentities($loggable, EventLog $eventLog): Collection
    {
        return Identity::where('id', $loggable->voucher->identity_id)->get();
    }
}
