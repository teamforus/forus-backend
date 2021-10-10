<?php

namespace App\Listeners;

use App\Events\ProductReservations\ProductReservationAccepted;
use App\Events\ProductReservations\ProductReservationCanceled;
use App\Events\ProductReservations\ProductReservationCreated;
use App\Events\ProductReservations\ProductReservationRejected;
use App\Models\ProductReservation;
use App\Notifications\Identities\ProductReservation\IdentityProductReservationAcceptedNotification;
use App\Notifications\Identities\ProductReservation\IdentityProductReservationCanceledNotification;
use App\Notifications\Identities\ProductReservation\IdentityProductReservationCreatedNotification;
use App\Notifications\Identities\ProductReservation\IdentityProductReservationRejectedNotification;
use Illuminate\Events\Dispatcher;

/**
 * Class ProductReservationSubscriber
 * @package App\Listeners
 */
class ProductReservationSubscriber
{
    /**
     * @param ProductReservation $productReservation
     * @return array
     */
    private function getReservationLogModels(ProductReservation $productReservation): array
    {
        return [
            'fund' => $productReservation->voucher->fund,
            'product' => $productReservation->product,
            'sponsor' =>  $productReservation->voucher->fund->organization,
            'provider' =>  $productReservation->product->organization,
            'voucher' => $productReservation->voucher,
            'employee' => $productReservation->employee,
            'implementation' => $productReservation->voucher->fund->getImplementation(),
            'product_reservation' => $productReservation,
        ];
    }

    /**
     * @param ProductReservationCreated $event
     */
    public function onProductReservationCreated(ProductReservationCreated $event): void
    {
        $productReservation = $event->getProductReservation();
        $voucher = $productReservation->voucher;

        if (!$voucher->parent_id && $voucher->usedCount() == 1) {
            $voucher->reportBackofficeFirstUse();
        }

        IdentityProductReservationCreatedNotification::send($productReservation->log(
            $productReservation::EVENT_CREATED,
            $this->getReservationLogModels($productReservation)
        ));
    }

    /**
     * @param ProductReservationAccepted $event
     */
    public function onProductReservationAccepted(ProductReservationAccepted $event): void
    {
        $productReservation = $event->getProductReservation();

        IdentityProductReservationAcceptedNotification::send($productReservation->log(
            $productReservation::EVENT_ACCEPTED,
            $this->getReservationLogModels($productReservation)
        ));
    }

    /**
     * @param ProductReservationCanceled $event
     */
    public function onProductReservationCanceled(ProductReservationCanceled $event): void
    {
        $productReservation = $event->getProductReservation();

        IdentityProductReservationCanceledNotification::send($productReservation->log(
            $productReservation::EVENT_CANCELED,
            $this->getReservationLogModels($productReservation)
        ));
    }

    /**
     * @param ProductReservationRejected $event
     */
    public function onProductReservationRejected(ProductReservationRejected $event): void
    {
        $productReservation = $event->getProductReservation();

        IdentityProductReservationRejectedNotification::send($productReservation->log(
            $productReservation::EVENT_REJECTED,
            $this->getReservationLogModels($productReservation)
        ));
    }

    /**
     * Handle the event.
     *
     * @param Dispatcher $events
     * @return void
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(
            ProductReservationCreated::class,
            '\App\Listeners\ProductReservationSubscriber@onProductReservationCreated'
        );

        $events->listen(
            ProductReservationAccepted::class,
            '\App\Listeners\ProductReservationSubscriber@onProductReservationAccepted'
        );

        $events->listen(
            ProductReservationRejected::class,
            '\App\Listeners\ProductReservationSubscriber@onProductReservationRejected'
        );

        $events->listen(
            ProductReservationCanceled::class,
            '\App\Listeners\ProductReservationSubscriber@onProductReservationCanceled'
        );
    }
}
