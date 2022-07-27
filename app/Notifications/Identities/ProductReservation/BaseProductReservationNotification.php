<?php

namespace App\Notifications\Identities\ProductReservation;

use App\Models\ProductReservation;
use App\Notifications\Identities\BaseIdentityNotification;
use App\Models\Identity;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Support\Collection;

/**
 * Class BaseProductReservationNotification
 * @package App\Notifications\Identities\ProductReservation
 */
abstract class BaseProductReservationNotification extends BaseIdentityNotification
{
    /**
     * Get identities which are eligible for the notification
     *
     * @param ProductReservation $loggable
     * @param EventLog $eventLog
     * @return \Illuminate\Support\Collection
     */
    public static function eligibleIdentities($loggable, EventLog $eventLog): Collection
    {
        return Identity::whereAddress($loggable->voucher->identity_address)->get();
    }
}
