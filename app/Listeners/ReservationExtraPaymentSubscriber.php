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
            $event->getReservationExtraPayment()->getLogModels($event->getEmployee()),
            $event->getData(),
        );
    }
}
