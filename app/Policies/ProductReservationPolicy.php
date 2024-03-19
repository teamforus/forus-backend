<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Voucher;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ProductReservationPolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @return bool
     * @noinspection PhpUnused
     */
    public function create(Identity $identity): bool
    {
        return $identity->exists;
    }

    /**
     * Determine whether the user can update the product reservation.
     *
     * @param Identity $identity
     * @param \App\Models\ProductReservation $productReservation
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function updateProvider(
        Identity $identity,
        ProductReservation $productReservation,
        Organization $organization
    ): bool {
        return
            $productReservation->product->organization_id === $organization->id &&
            $organization->identityCan($identity, 'scan_vouchers');
    }
}
