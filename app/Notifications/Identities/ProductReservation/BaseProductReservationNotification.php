<?php

namespace App\Notifications\Identities\ProductReservation;

use App\Models\ProductReservation;
use App\Notifications\Identities\BaseIdentityNotification;
use App\Models\Identity;
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
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public static function eligibleIdentities($loggable): Collection
    {
        return Identity::whereAddress($loggable->voucher->identity_address)->get();
    }
}
