<?php

namespace App\Listeners;

use App\Events\ProductReservations\ProductReservationAccepted;
use App\Events\ProductReservations\ProductReservationCanceled;
use App\Events\ProductReservations\ProductReservationCreated;
use App\Events\ProductReservations\ProductReservationRejected;
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
     * @param ProductReservationCreated $event
     */
    public function onProductReservationCreated(ProductReservationCreated $event): void
    {
        $productReservation = $event->getProductReservation();
        $voucher = $productReservation->voucher;

        if (!$voucher->parent_id && $voucher->usedCount() == 1) {
            $voucher->reportBackofficeFirstUse();
        }

        $logEvent = $productReservation->log($productReservation::EVENT_CREATED, [
            'fund' => $productReservation->voucher->fund,
            'product' => $productReservation->product,
            'sponsor' =>  $productReservation->voucher->fund->organization,
            'provider' =>  $productReservation->product->organization,
            'voucher' => $productReservation->voucher,
            'employee' => $productReservation->employee,
            'product_reservation' => $productReservation,
        ]);

        IdentityProductReservationCreatedNotification::send($logEvent);
    }

    /**
     * @param ProductReservationAccepted $event
     */
    public function onProductReservationAccepted(ProductReservationAccepted $event): void
    {
        $productReservation = $event->getProductReservation();

        $logEvent = $productReservation->log($productReservation::EVENT_ACCEPTED, [
            'fund' => $productReservation->voucher->fund,
            'product' => $productReservation->product,
            'sponsor' =>  $productReservation->voucher->fund->organization,
            'provider' =>  $productReservation->product->organization,
            'voucher' => $productReservation->voucher,
            'employee' => $productReservation->employee,
            'product_reservation' => $productReservation,
        ]);

        IdentityProductReservationAcceptedNotification::send($logEvent);
    }

    /**
     * @param ProductReservationCanceled $event
     */
    public function onProductReservationCanceled(ProductReservationCanceled $event): void
    {
        $productReservation = $event->getProductReservation();

        $logEvent = $productReservation->log($productReservation::EVENT_CANCELED, [
            'fund' => $productReservation->voucher->fund,
            'product' => $productReservation->product,
            'sponsor' =>  $productReservation->voucher->fund->organization,
            'provider' =>  $productReservation->product->organization,
            'voucher' => $productReservation->voucher,
            'employee' => $productReservation->employee,
            'product_reservation' => $productReservation,
        ]);

        IdentityProductReservationCanceledNotification::send($logEvent);
    }

    /**
     * @param ProductReservationRejected $event
     */
    public function onProductReservationRejected(ProductReservationRejected $event): void
    {
        $productReservation = $event->getProductReservation();

        $logEvent = $productReservation->log($productReservation::EVENT_REJECTED, [
            'fund' => $productReservation->voucher->fund,
            'product' => $productReservation->product,
            'sponsor' =>  $productReservation->voucher->fund->organization,
            'provider' =>  $productReservation->product->organization,
            'voucher' => $productReservation->voucher,
            'employee' => $productReservation->employee,
            'product_reservation' => $productReservation,
        ]);

        IdentityProductReservationRejectedNotification::send($logEvent);
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
