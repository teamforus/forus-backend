<?php

namespace App\Notifications\Organizations\ProductReservations;

use App\Models\Organization;
use App\Notifications\Organizations\BaseOrganizationNotification;

abstract class BaseProductReservationsNotification extends BaseOrganizationNotification
{
    protected static ?string $scope = self::SCOPE_PROVIDER;

    /**
     * @param \App\Models\ProductReservation $loggable
     * @return \App\Models\Organization
     */
    public static function getOrganization(mixed $loggable): Organization
    {
        return $loggable->product->organization;
    }
}
