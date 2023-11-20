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
     * Determine whether the user can view any product reservations.
     *
     * @param Identity $identity
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewAny(Identity $identity): bool
    {
        return $identity->exists;
    }

    /**
     * Determine whether the user can view any product reservations.
     *
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewAnyProvider(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, 'scan_vouchers');
    }

    /**
     * Determine whether the user can view the product reservation.
     *
     * @param Identity $identity
     * @param \App\Models\ProductReservation $productReservation
     * @return bool
     * @noinspection PhpUnused
     */
    public function view(Identity $identity, ProductReservation $productReservation): bool
    {
        return $productReservation->voucher->identity_address === $identity->address;
    }

    /**
     * Determine whether the user can view the product reservation.
     *
     * @param Identity $identity
     * @param \App\Models\ProductReservation $productReservation
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewProvider(
        Identity $identity,
        ProductReservation $productReservation,
        Organization $organization
    ): bool {
        return $this->updateProvider($identity, $productReservation, $organization);
    }

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
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function createProvider(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, 'scan_vouchers');
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function createProviderBatch(Identity $identity, Organization $organization): bool
    {
        return
            $organization->allow_batch_reservations &&
            $organization->identityCan($identity, 'scan_vouchers');
    }

    /**
     * Determine whether the user can update the product reservation.
     *
     * @param Identity $identity
     * @param  \App\Models\ProductReservation  $productReservation
     * @return bool
     * @noinspection PhpUnused
     */
    public function update(Identity $identity, ProductReservation $productReservation): bool
    {
        return
            $productReservation->isCancelableByRequester() &&
            $productReservation->voucher->identity_address === $identity->address;
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
            $productReservation->product->organization_id == $organization->id &&
            $organization->identityCan($identity, 'scan_vouchers');
    }

    /**
     * Determine whether the user can update the product reservation.
     *
     * @param Identity $identity
     * @param \App\Models\ProductReservation $productReservation
     * @param Organization $organization
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function acceptProvider(
        Identity $identity,
        ProductReservation $productReservation,
        Organization $organization
    ): Response|bool {
        if (!$this->updateProvider($identity, $productReservation, $organization)) {
            return false;
        }

        if (!$productReservation->voucher->activated) {
            return $this->deny('The voucher used to make the reservation, is not active.');
        }

        if ($productReservation->voucher->expired) {
            return $this->deny('The voucher used to make the reservation, has expired.');
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
     * @param Identity $identity
     * @param \App\Models\ProductReservation $productReservation
     * @param Organization $organization
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function rejectProvider(
        Identity $identity,
        ProductReservation $productReservation,
        Organization $organization
    ): Response|bool {
        if (!$productReservation->voucher->activated && !$productReservation->isAccepted()) {
            return $this->deny('The voucher used to make the reservation, is not active.');
        }

        if ($productReservation->voucher->expired && !$productReservation->isAccepted()) {
            return $this->deny('The voucher used to make the reservation, has expired.');
        }

        return $this->updateProvider($identity, $productReservation, $organization) &&
            $productReservation->isCancelableByProvider();
    }

    /**
     * Determine whether the user can delete the product reservation.
     *
     * @param Identity $identity
     * @param  \App\Models\ProductReservation  $productReservation
     * @return bool
     * @noinspection PhpUnused
     */
    public function delete(Identity $identity, ProductReservation $productReservation): bool
    {
        return $this->update($identity, $productReservation) && $productReservation->isPending();
    }

    /**
     * @param Identity $identity
     * @param ProductReservation $productReservation
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function archive(
        Identity $identity,
        ProductReservation $productReservation,
        Organization $organization
    ): bool {
        return
            $productReservation->isArchivable() &&
            $this->updateProvider($identity, $productReservation, $organization);
    }

    /**
     * @param Identity $identity
     * @param ProductReservation $productReservation
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function unarchive(
        Identity $identity,
        ProductReservation $productReservation,
        Organization $organization
    ): bool {
        return
            $productReservation->archived &&
            $this->updateProvider($identity, $productReservation, $organization);
    }

    /**
     * @param Identity $identity
     * @param Product $product
     * @param Voucher $voucher
     * @return bool
     */
    public function createExtraPayment(Identity $identity, Product $product, Voucher $voucher): bool
    {
        return $identity->exists && $product->reservationExtraPaymentsEnabled($voucher->fund);
    }

    /**
     * @param Identity $identity
     * @param  ProductReservation $reservation
     * @return bool
     * @noinspection PhpUnused
     */
    public function payExtraPayment(Identity $identity, ProductReservation $reservation): bool
    {
        return
            $reservation->isWaiting() &&
            $reservation->extra_amount > 0 &&
            $reservation->voucher->identity_address === $identity->address &&
            $reservation->product->reservationExtraPaymentsEnabled($reservation->voucher->fund);
    }

    /**
     * @param Identity $identity
     * @param ProductReservation $productReservation
     * @param Organization $organization
     * @return bool
     */
    public function fetchExtraPayment(
        Identity $identity,
        ProductReservation $productReservation,
        Organization $organization
    ): bool {
        return $this->updateProvider($identity, $productReservation, $organization);
    }

    /**
     * @param Identity $identity
     * @param ProductReservation $productReservation
     * @param Organization $organization
     * @return bool
     */
    public function refundExtraPayment(
        Identity $identity,
        ProductReservation $productReservation,
        Organization $organization
    ): bool {
        return $this->updateProvider($identity, $productReservation, $organization);
    }
}
