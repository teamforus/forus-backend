<?php

namespace App\Listeners;

use App\Events\MollieConnections\MollieConnectionCompleted;
use App\Events\MollieConnections\MollieConnectionCreated;
use App\Events\MollieConnections\MollieConnectionCurrentProfileChanged;
use App\Events\MollieConnections\MollieConnectionDeleted;
use App\Events\MollieConnections\MollieConnectionUpdated;
use App\Events\MollieConnections\BaseMollieConnectionEvent;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Events\Dispatcher;

class MollieConnectionSubscriber
{
    /**
     * @param BaseMollieConnectionEvent $event
     * @param string $eventType
     * @return EventLog
     * @noinspection PhpUnused
     */
    protected function makeEvent(BaseMollieConnectionEvent $event, string $eventType): EventLog
    {
        return $event->getMollieConnection()->log(
            $eventType,
            $event->getMollieConnection()->getLogModels($event->getEmployee()),
            $event->getData(),
        );
    }

    /**
     * @param MollieConnectionCreated $event
     * @noinspection PhpUnused
     */
    public function onMollieConnectionCreated(MollieConnectionCreated $event): void
    {
        $this->makeEvent($event, $event->getMollieConnection()::EVENT_CREATED);
    }

    /**
     * @param MollieConnectionUpdated $event
     * @noinspection PhpUnused
     */
    public function onMollieConnectionUpdated(MollieConnectionUpdated $event): void
    {
        $this->makeEvent($event, $event->getMollieConnection()::EVENT_UPDATED);
    }

    /**
     * @param MollieConnectionCompleted $event
     * @noinspection PhpUnused
     */
    public function onMollieConnectionCompleted(MollieConnectionCompleted $event): void
    {
        $this->makeEvent($event, $event->getMollieConnection()::EVENT_COMPLETED);
    }

    /**
     * @param MollieConnectionDeleted $event
     * @noinspection PhpUnused
     */
    public function onMollieConnectionDeleted(MollieConnectionDeleted $event): void
    {
        $this->makeEvent($event, $event->getMollieConnection()::EVENT_DELETED);
    }

    /**
     * @param MollieConnectionCurrentProfileChanged $event
     * @noinspection PhpUnused
     */
    public function onMollieConnectionCurrentProfileChanged(MollieConnectionCurrentProfileChanged $event): void
    {
        $this->makeEvent($event, $event->getMollieConnection()::EVENT_CURRENT_PROFILE_CHANGED);
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

        $events->listen(MollieConnectionCreated::class, "$class@onMollieConnectionCreated");
        $events->listen(MollieConnectionCompleted::class, "$class@onMollieConnectionCompleted");
        $events->listen(MollieConnectionUpdated::class, "$class@onMollieConnectionUpdated");
        $events->listen(MollieConnectionDeleted::class, "$class@onMollieConnectionDeleted");
        $events->listen(MollieConnectionCurrentProfileChanged::class, "$class@onMollieConnectionCurrentProfileChanged");
    }
}
