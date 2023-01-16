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
use App\Notifications\Organizations\ProductReservations\ProductReservationCanceledNotification;
use Illuminate\Events\Dispatcher;

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
     * @noinspection PhpUnused
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
     * @noinspection PhpUnused
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
     * @noinspection PhpUnused
     */
    public function onProductReservationCanceled(ProductReservationCanceled $event): void
    {
        $productReservation = $event->getProductReservation();

        if ($productReservation->isCanceledByClient()) {
            ProductReservationCanceledNotification::send($productReservation->log(
                $productReservation::EVENT_CANCELED_BY_CLIENT,
                $this->getReservationLogModels($productReservation),
            ));
        }

        if ($productReservation->isCanceledByProvider()) {
            IdentityProductReservationCanceledNotification::send($productReservation->log(
                $productReservation::EVENT_CANCELED_BY_PROVIDER,
                $this->getReservationLogModels($productReservation),
            ));
        }
    }

    /**
     * @param ProductReservationRejected $event
     * @noinspection PhpUnused
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
     * @noinspection PhpUnused
     */
    public function subscribe(Dispatcher $events): void
    {
        $class = '\\' . static::class;

        $events->listen(ProductReservationCreated::class, "$class@onProductReservationCreated");
        $events->listen(ProductReservationAccepted::class, "$class@onProductReservationAccepted");
        $events->listen(ProductReservationRejected::class, "$class@onProductReservationRejected");
        $events->listen(ProductReservationCanceled::class, "$class@onProductReservationCanceled");
    }
}
