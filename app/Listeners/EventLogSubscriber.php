<?php

namespace App\Listeners;

use App\Models\EventLogRelation;
use App\Services\EventLogService\Events\EventLogCreated;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Arr;

class EventLogSubscriber
{
    /**
     * @param EventLogCreated $logCreated
     * @noinspection PhpUnused
     */
    public function onEventLogCreated(EventLogCreated $logCreated): void
    {
        $log = $logCreated->getEventLog();

        if ($fund_id = Arr::get($log->data, 'fund_id')) {
            EventLogRelation::updateOrCreate([
                'event_log_id' => $log->id,
            ], [
                'fund_id' => $fund_id,
            ]);
        }
    }

    /**
     * The events dispatcher.
     *
     * @param Dispatcher $events
     * @return void
     * @noinspection PhpUnused
     */
    public function subscribe(Dispatcher $events): void
    {
        $class = '\\' . static::class;

        $events->listen(EventLogCreated::class, "$class@onEventLogCreated");
    }
}
