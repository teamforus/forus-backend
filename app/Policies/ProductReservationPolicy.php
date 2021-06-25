<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\ProductReservation;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class ProductReservationPolicy
 * @package App\Policies
 */
class ProductReservationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any product reservations.
     *
     * @param string $identity_address
     * @return bool
     */
    public function viewAny(string $identity_address): bool
    {
        return !empty($identity_address);
    }

    /**
     * Determine whether the user can view any product reservations.
     *
     * @param string $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function viewAnyProvider(string $identity_address, Organization $organization): bool
    {
        return $identity_address && $organization->identityCan($identity_address, 'scan_vouchers');
    }

    /**
     * Determine whether the user can view the product reservation.
     *
     * @param string $identity_address
     * @param \App\Models\ProductReservation $productReservation
     * @return bool
     */
    public function view(string $identity_address, ProductReservation $productReservation): bool
    {
        return $this->update($identity_address, $productReservation);
    }

    /**
     * Determine whether the user can view the product reservation.
     *
     * @param string $identity_address
     * @param \App\Models\ProductReservation $productReservation
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewProvider(
        string $identity_address,
        ProductReservation $productReservation,
        Organization $organization
    ): bool {
        return $this->updateProvider($identity_address, $productReservation, $organization);
    }

    /**
     * @param string $identity_address
     * @return bool
     */
    public function create(string $identity_address): bool
    {
        return !empty($identity_address);
    }

    /**
     * @param string $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function createProvider(string $identity_address, Organization $organization): bool
    {
        return $identity_address && $organization->identityCan($identity_address, 'scan_vouchers');
    }

    /**
     * Determine whether the user can update the product reservation.
     *
     * @param string $identity_address
     * @param  \App\Models\ProductReservation  $productReservation
     * @return bool
     */
    public function update(string $identity_address, ProductReservation $productReservation): bool
    {
        return $productReservation->voucher->identity_address === $identity_address;
    }

    /**
     * Determine whether the user can update the product reservation.
     *
     * @param string $identity_address
     * @param \App\Models\ProductReservation $productReservation
     * @param Organization $organization
     * @noinspection PhpUnused
     * @return bool
     */
    public function updateProvider(
        string $identity_address,
        ProductReservation $productReservation,
        Organization $organization
    ): bool {
        return !empty($identity_address) &&
            $productReservation->product->organization_id == $organization->id &&
            $organization->identityCan($identity_address, 'scan_vouchers');
    }

    /**
     * Determine whether the user can update the product reservation.
     *
     * @param string $identity_address
     * @param \App\Models\ProductReservation $productReservation
     * @param Organization $organization
     * @noinspection PhpUnused
     * @return mixed
     */
    public function acceptProvider(
        string $identity_address,
        ProductReservation $productReservation,
        Organization $organization
    ) {
        if (!$this->updateProvider($identity_address, $productReservation, $organization)) {
            return false;
        }

        if (!$productReservation->isPending()) {
            return $this->deny('Not pending.');
        }

        if ($productReservation->hasExpired()) {
            return $this->deny('Reservation expired.');
        }

        if ($productReservation->product->trashed()) {
            return $this->deny(sprintf(
                "The product '%s' removed by the provider.",
                $productReservation->product->name
            ));
        }

        return true;
    }

    /**
     * Determine whether the user can update the product reservation.
     *
     * @param string $identity_address
     * @param \App\Models\ProductReservation $productReservation
     * @param Organization $organization
     * @noinspection PhpUnused
     * @return bool
     */
    public function rejectProvider(
        string $identity_address,
        ProductReservation $productReservation,
        Organization $organization
    ): bool {
        return $this->updateProvider($identity_address, $productReservation, $organization) &&
            $productReservation->isCancelableByProvider();
    }

    /**
     * Determine whether the user can delete the product reservation.
     *
     * @param string $identity_address
     * @param  \App\Models\ProductReservation  $productReservation
     * @return bool
     */
    public function delete(string $identity_address, ProductReservation $productReservation): bool
    {
        return $this->update($identity_address, $productReservation) &&
            $productReservation->isPending();
    }
}
