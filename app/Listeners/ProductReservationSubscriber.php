<?php

namespace App\Listeners;

use App\Events\ProductReservations\ProductReservationAccepted;
use App\Events\ProductReservations\ProductReservationCanceled;
use App\Events\ProductReservations\ProductReservationCreated;
use App\Events\ProductReservations\ProductReservationPending;
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
     *
     * @return (ProductReservation|\App\Models\Employee|\App\Models\Fund|\App\Models\Implementation|\App\Models\Organization|\App\Models\Product|\App\Models\Voucher|null)[]
     *
     * @psalm-return array{fund: \App\Models\Fund, product: \App\Models\Product, sponsor: \App\Models\Organization, provider: \App\Models\Organization, voucher: \App\Models\Voucher, employee: \App\Models\Employee|null, implementation: \App\Models\Implementation, product_reservation: ProductReservation}
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
}
