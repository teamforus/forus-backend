<?php

namespace App\Listeners;

use App\Events\ReservationExtraPayments\BaseReservationExtraPaymentEvent;
use App\Events\ReservationExtraPayments\ReservationExtraPaymentCanceled;
use App\Events\ReservationExtraPayments\ReservationExtraPaymentCreated;
use App\Events\ReservationExtraPayments\ReservationExtraPaymentPaid;
use App\Events\ReservationExtraPayments\ReservationExtraPaymentRefunded;
use App\Events\ReservationExtraPayments\ReservationExtraPaymentUpdated;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Events\Dispatcher;

class ReservationExtraPaymentSubscriber
{
    /**
     * @param BaseReservationExtraPaymentEvent $event
     * @param string $eventType
     * @return EventLog
     */
    protected function makeEvent(BaseReservationExtraPaymentEvent $event, string $eventType): EventLog
    {
        return $event->getReservationExtraPayment()->log(
            $eventType,
            $event->getReservationExtraPayment()->getLogModels()
        );
    }

    /**
     * @param ReservationExtraPaymentCreated $event
     * @noinspection PhpUnused
     */
    public function onReservationExtraPaymentCreated(ReservationExtraPaymentCreated $event): void
    {
        $this->makeEvent($event, $event->getReservationExtraPayment()::EVENT_CREATED);
    }

    /**
     * @param ReservationExtraPaymentCanceled $event
     * @throws \Throwable
     */
    public function onReservationExtraPaymentCanceled(ReservationExtraPaymentCanceled $event): void
    {
        $this->makeEvent($event, $event->getReservationExtraPayment()::EVENT_CANCELED);
    }

    /**
     * @param ReservationExtraPaymentUpdated $event
     */
    public function onReservationExtraPaymentUpdated(ReservationExtraPaymentUpdated $event): void
    {
        $this->makeEvent($event, $event->getReservationExtraPayment()::EVENT_UPDATED);
    }

    /**
     * @param ReservationExtraPaymentRefunded $event
     * @throws \Throwable
     */
    public function onReservationExtraPaymentRefunded(ReservationExtraPaymentRefunded $event): void
    {
        $this->makeEvent($event, $event->getReservationExtraPayment()::EVENT_REFUNDED);
    }

    /**
     * @param ReservationExtraPaymentPaid $event
     * @throws \Throwable
     */
    public function onReservationExtraPaymentPaid(ReservationExtraPaymentPaid $event): void
    {
        $this->makeEvent($event, $event->getReservationExtraPayment()::EVENT_PAID);
        $reservation = $event->getProductReservation();
        $reservation->setPending();

        if ($reservation->product->autoAcceptsReservations($reservation->voucher->fund)) {
            $reservation->acceptProvider();
        }
    }

    /**
     * The events dispatcher
     *
     * @param Dispatcher $events
     * @noinspection PhpUnused
     */
    public function subscribe(Dispatcher $events): void
    {
        $class = '\\' . static::class;

        $events->listen(ReservationExtraPaymentCreated::class, "$class@onReservationExtraPaymentCreated");
        $events->listen(ReservationExtraPaymentUpdated::class, "$class@onReservationExtraPaymentUpdated");
        $events->listen(ReservationExtraPaymentPaid::class, "$class@onReservationExtraPaymentPaid");
        $events->listen(ReservationExtraPaymentCanceled::class, "$class@onReservationExtraPaymentCanceled");
        $events->listen(ReservationExtraPaymentRefunded::class, "$class@onReservationExtraPaymentRefunded");
    }
}
