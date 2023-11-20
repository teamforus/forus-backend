<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Organization;
use App\Models\ReservationExtraPayment;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReservationExtraPaymentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any product reservations.
     *
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function viewAnySponsor(Identity $identity, Organization $organization): bool
    {
        return $organization->isEmployee($identity);
    }

    /**
     * @param Identity $identity
     * @param ReservationExtraPayment $payment
     * @param Organization $organization
     * @return bool
     */
    public function viewSponsor(
        Identity $identity,
        ReservationExtraPayment $payment,
        Organization $organization
    ): bool {
        return $organization->isEmployee($identity) &&
            $payment->product_reservation->voucher->fund->organization_id === $organization->id;
    }
}
