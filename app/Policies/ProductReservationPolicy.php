<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
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
        return $organization->identityCan($identity, Permission::SCAN_VOUCHERS);
    }

    /**
     * Determine whether the user can view any product reservations.
     *
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewAnySponsor(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, [
            Permission::MANAGE_VOUCHERS,
            Permission::VIEW_VOUCHERS,
        ], false);
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
        return $productReservation->voucher->identity_id === $identity->id;
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
        return $organization->identityCan($identity, Permission::SCAN_VOUCHERS);
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
            $organization->identityCan($identity, Permission::SCAN_VOUCHERS);
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

        if ($productReservation->voucher->identity_id !== $identity->id) {
            return false;
        }

        if (!$productReservation->extra_payment) {
            return true;
        }

        $expiresIn = $productReservation->expiresIn();
        $isCancelable = $productReservation->extra_payment->isCancelable();

        if ($isCancelable || ($productReservation->isWaiting() && $expiresIn <= 0)) {
            return true;
        }

        $time = now()->addSeconds($expiresIn)->diffForHumans(now());

        return $this->deny(__('policies.reservations.timeout_extra_payment', [
            'time' => $time,
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
            $organization->identityCan($identity, Permission::SCAN_VOUCHERS);
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

        if ($productReservation->extra_payment && $productReservation->extra_payment->isPartlyRefunded()) {
            return $this->deny('Extra payment is partly refunded.');
        }

        if ($productReservation->extra_payment && $productReservation->extra_payment->isFullyRefunded()) {
            return $this->deny('Extra payment is refunded.');
        }

        if ($productReservation->extra_payment && $productReservation->extra_payment->hasPendingRefunds()) {
            return $this->deny('Extra payment has pending refunds.');
        }

        if (!$productReservation->voucher->activated) {
            return $this->deny('The voucher used to make the reservation, is not active.');
        }

        if ($productReservation->voucher->reservation_approval_time_expired) {
            return $this->deny('The voucher used to make the reservation, has expired.');
        }

        if (!$productReservation->isPending()) {
            return $this->deny('Not pending.');
        }

        if ($productReservation->isExpired()) {
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

        if ($productReservation->isExpired()) {
            return $this->deny('Reservation expired.');
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
            return $this->deny(__('policies.reservations.not_waiting'));
        }

        if (!$reservation->extra_payment?->payment_id) {
            return $this->deny(__('policies.reservations.extra_payment_invalid'));
        }

        if ($reservation->extra_payment?->isPaid()) {
            return $this->deny(__('policies.reservations.extra_payment_is_paid'));
        }

        if ($reservation->extra_payment?->expiresIn() <= 30) {
            return $this->deny(__('policies.reservations.extra_payment_time_expired'));
        }

        return $reservation->voucher->identity_id === $identity->id;
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
            $productReservation->extra_payment &&
            $productReservation->extra_payment->isPaid() &&
            $productReservation->extra_payment->isRefundable() &&
            $productReservation->extra_payment->isMollieType();
    }

    /**
     * Determine whether the user can update the product reservation invoice number.
     *
     * @param Identity $identity
     * @param ProductReservation $productReservation
     * @param Organization $organization
     * @return bool
     */
    public function update(
        Identity $identity,
        ProductReservation $productReservation,
        Organization $organization
    ): bool {
        return $this->updateProvider($identity, $productReservation, $organization);
    }
}
