<?php

namespace App\Listeners;

use App\Events\ReservationExtraPayments\BaseReservationExtraPaymentEvent;
use App\Events\ReservationExtraPayments\ReservationExtraPaymentCanceled;
use App\Events\ReservationExtraPayments\ReservationExtraPaymentCreated;
use App\Events\ReservationExtraPayments\ReservationExtraPaymentExpired;
use App\Events\ReservationExtraPayments\ReservationExtraPaymentFailed;
use App\Events\ReservationExtraPayments\ReservationExtraPaymentPaid;
use App\Events\ReservationExtraPayments\ReservationExtraPaymentRefunded;
use App\Events\ReservationExtraPayments\ReservationExtraPaymentRefundedApi;
use App\Events\ReservationExtraPayments\ReservationExtraPaymentUpdated;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Events\Dispatcher;
use Throwable;

class ReservationExtraPaymentSubscriber
{
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
     * @throws Throwable
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
     * @throws Throwable
     */
    public function onReservationExtraPaymentRefunded(ReservationExtraPaymentRefunded $event): void
    {
        $this->makeEvent($event, $event->getReservationExtraPayment()::EVENT_REFUNDED);

        $event->getReservationExtraPayment()->product_reservation->rejectOrCancelProvider();
    }

    /**
     * @param ReservationExtraPaymentRefundedApi $event
     * @throws Throwable
     */
    public function onReservationExtraPaymentRefundedApi(ReservationExtraPaymentRefundedApi $event): void
    {
        $this->makeEvent($event, $event->getReservationExtraPayment()::EVENT_REFUNDED_API);
    }

    /**
     * @param ReservationExtraPaymentFailed $event
     * @throws Throwable
     */
    public function onReservationExtraPaymentFailed(ReservationExtraPaymentFailed $event): void
    {
        $this->makeEvent($event, $event->getReservationExtraPayment()::EVENT_FAILED);
    }

    /**
     * @param ReservationExtraPaymentExpired $event
     * @throws Throwable
     */
    public function onReservationExtraPaymentExpired(ReservationExtraPaymentExpired $event): void
    {
        $this->makeEvent($event, $event->getReservationExtraPayment()::EVENT_EXPIRED);
    }

    /**
     * @param ReservationExtraPaymentPaid $event
     * @throws Throwable
     */
    public function onReservationExtraPaymentPaid(ReservationExtraPaymentPaid $event): void
    {
        $this->makeEvent($event, $event->getReservationExtraPayment()::EVENT_PAID);
    }

    /**
     * The events dispatcher.
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
        $events->listen(ReservationExtraPaymentFailed::class, "$class@onReservationExtraPaymentFailed");
        $events->listen(ReservationExtraPaymentExpired::class, "$class@onReservationExtraPaymentExpired");
        $events->listen(ReservationExtraPaymentRefundedApi::class, "$class@onReservationExtraPaymentRefundedApi");
    }

    /**
     * @param BaseReservationExtraPaymentEvent $event
     * @param string $eventType
     * @return EventLog
     */
    protected function makeEvent(BaseReservationExtraPaymentEvent $event, string $eventType): EventLog
    {
        return $event->getReservationExtraPayment()->log(
            $eventType,
            $event->getReservationExtraPayment()->getLogModels($event->getEmployee()),
            $event->getData(),
        );
    }
}
