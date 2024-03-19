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
}
