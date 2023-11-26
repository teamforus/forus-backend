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
     * @param ProductReservation $productReservation
     * @return Response|bool
     */
    public function cancelAsRequester(
        Identity $identity,
        ProductReservation $productReservation,
    ): Response|bool {
        if (!$productReservation->isWaiting() && !$productReservation->isPending()) {
            return $this->deny('Only pending reservations can be canceled.');
        }

        if ($productReservation->voucher->identity_address !== $identity->address) {
            return false;
        }

        if (!$productReservation->extra_payment) {
            return true;
        }

        $expiresIn = $productReservation->expiresIn();
        $isCancelable = $productReservation->extra_payment->isCancelable();

        if ($isCancelable || $expiresIn <= 0) {
            return true;
        }

        return $this->deny(implode(" ", [
            "Het is op dit moment niet mogelijk om uw reservering te annuleren.",
            "Probeer het " . now()->addSeconds($expiresIn)->diffForHumans(now()) . ".",
        ]));
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

        if ($productReservation->extra_payment && !$productReservation->extra_payment->isPaid()) {
            return $this->deny('Extra payment not paid.');
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

        return
            $this->updateProvider($identity, $productReservation, $organization) &&
            $productReservation->isCancelableByProvider();
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
        return
            $identity->exists &&
            $product->reservationExtraPaymentsEnabled($voucher->fund);
    }

    /**
     * @param Identity $identity
     * @param ProductReservation $reservation
     * @return Response|bool
     */
    public function checkoutExtraPayment(Identity $identity, ProductReservation $reservation): Response|bool
    {
        if (!$reservation->isWaiting()) {
            return $this->deny('Reservation payment not waiting.');
        }

        if (!$reservation->extra_payment?->payment_id) {
            return $this->deny('Invalid reservation.');
        }

        if ($reservation->extra_payment?->isPaid()) {
            return $this->deny('Extra payment already paid.');
        }

        if ($reservation->extra_payment?->expiresIn() <= 30) {
            return $this->deny('Checkout time expired expired.');
        }

        return $reservation->voucher->identity_address === $identity->address;
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
        Organization $organization,
    ): bool {
        return
            $this->updateProvider($identity, $productReservation, $organization) &&
            $productReservation->extra_payment?->isMollieType();
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
        Organization $organization,
    ): bool {
        return
            $this->updateProvider($identity, $productReservation, $organization) &&
            $productReservation->extra_payment?->isPaid() &&
            $productReservation->extra_payment?->refunds()->doesntExist() &&
            $productReservation->extra_payment?->isMollieType();
    }
}
