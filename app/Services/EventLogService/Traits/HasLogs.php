<?php

namespace App\Services\EventLogService\Traits;

use App\Services\EventLogService\Interfaces\IEventLogService;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Trait HasLogs
 * @mixin \Eloquent
 * @package App\Models\Traits
 */
trait HasLogs
{
    /**
     * @param string $event
     * @param $models
     * @param array $raw_meta
     * @return EventLog
     */
    public function log(
        string $event,
        array $models = [],
        array $raw_meta = []
    ): EventLog {
        $meta = array_reduce(array_keys($models), static function($carry, $key) use ($models) {
            return array_merge($carry, resolve(IEventLogService::class)->modelToMeta(
                $key, $models[$key]
            ));
        }, []);

        $data = array_merge($meta, $raw_meta);

        /** @var EventLog $eventLog */
        $eventLog = $this->logs()->create([
            'event' => $event,
            'data' => $data,
            'identity_address' => auth_address()
        ]);

        return $eventLog;
    }

    /**
     * @return MorphMany
     */
    public function logs(): MorphMany
    {
        return $this->morphMany(EventLog::class, 'loggable');
    }
}