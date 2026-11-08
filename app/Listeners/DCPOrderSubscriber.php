<?php

namespace App\Listeners;

use App\Events\DCPOrders\BaseDCPOrderEvent;
use App\Events\DCPOrders\DCPOrderApproved;
use App\Events\DCPOrders\DCPOrderCreated;
use App\Events\DCPOrders\DCPOrderFailed;
use App\Events\DCPOrders\DCPOrderIdDcpSet;
use App\Events\DCPOrders\DCPOrderPayloadReset;
use App\Events\DCPOrders\DCPOrderPayloadUpdated;
use App\Events\DCPOrders\DCPOrderRetried;
use App\Events\DCPOrders\DCPOrderSent;
use App\Events\DCPOrders\DCPOrderStatusChanged;
use App\Services\DCPService\Models\DCPOrder;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Events\Dispatcher;

class DCPOrderSubscriber
{
    /**
     * @param DCPOrderCreated $event
     * @noinspection PhpUnused
     */
    public function onDCPOrderCreated(DCPOrderCreated $event): void
    {
        $this->makeEvent($event, DCPOrder::EVENT_CREATED);
    }

    /**
     * @param DCPOrderApproved $event
     * @noinspection PhpUnused
     */
    public function onDCPOrderApproved(DCPOrderApproved $event): void
    {
        $this->makeEvent($event, DCPOrder::EVENT_APPROVED);
    }

    /**
     * @param DCPOrderSent $event
     * @noinspection PhpUnused
     */
    public function onDCPOrderSent(DCPOrderSent $event): void
    {
        $this->makeEvent($event, DCPOrder::EVENT_SENT);
    }

    /**
     * @param DCPOrderFailed $event
     * @noinspection PhpUnused
     */
    public function onDCPOrderFailed(DCPOrderFailed $event): void
    {
        $this->makeEvent($event, DCPOrder::EVENT_FAILED);
    }

    /**
     * @param DCPOrderStatusChanged $event
     * @noinspection PhpUnused
     */
    public function onDCPOrderStatusChanged(DCPOrderStatusChanged $event): void
    {
        $this->makeEvent($event, DCPOrder::EVENT_STATUS_CHANGED);
    }

    /**
     * @param DCPOrderRetried $event
     * @noinspection PhpUnused
     */
    public function onDCPOrderRetried(DCPOrderRetried $event): void
    {
        $this->makeEvent($event, DCPOrder::EVENT_RETRIED);
    }

    /**
     * @param DCPOrderPayloadUpdated $event
     * @noinspection PhpUnused
     */
    public function onDCPOrderPayloadUpdated(DCPOrderPayloadUpdated $event): void
    {
        $this->makeEvent($event, DCPOrder::EVENT_PAYLOAD_UPDATED);
    }

    /**
     * @param DCPOrderPayloadReset $event
     * @noinspection PhpUnused
     */
    public function onDCPOrderPayloadReset(DCPOrderPayloadReset $event): void
    {
        $this->makeEvent($event, DCPOrder::EVENT_PAYLOAD_RESET);
    }

    /**
     * @param DCPOrderIdDcpSet $event
     * @noinspection PhpUnused
     */
    public function onDCPOrderIdDcpSet(DCPOrderIdDcpSet $event): void
    {
        $this->makeEvent($event, DCPOrder::EVENT_ORDER_ID_DCP_SET);
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

        $events->listen(DCPOrderCreated::class, "$class@onDCPOrderCreated");
        $events->listen(DCPOrderApproved::class, "$class@onDCPOrderApproved");
        $events->listen(DCPOrderSent::class, "$class@onDCPOrderSent");
        $events->listen(DCPOrderFailed::class, "$class@onDCPOrderFailed");
        $events->listen(DCPOrderStatusChanged::class, "$class@onDCPOrderStatusChanged");
        $events->listen(DCPOrderRetried::class, "$class@onDCPOrderRetried");
        $events->listen(DCPOrderPayloadUpdated::class, "$class@onDCPOrderPayloadUpdated");
        $events->listen(DCPOrderPayloadReset::class, "$class@onDCPOrderPayloadReset");
        $events->listen(DCPOrderIdDcpSet::class, "$class@onDCPOrderIdDcpSet");
    }

    /**
     * @param BaseDCPOrderEvent $event
     * @param string $eventType
     * @return EventLog
     */
    protected function makeEvent(BaseDCPOrderEvent $event, string $eventType): EventLog
    {
        $order = $event->getDCPOrder();

        return $order->log(
            $eventType,
            $order->getLogModels($event->getEmployee()),
            $event->getData(),
            $event->getEmployee()?->identity_address,
        );
    }
}
