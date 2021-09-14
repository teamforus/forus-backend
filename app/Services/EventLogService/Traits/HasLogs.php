<?php

namespace App\Services\EventLogService\Traits;

use App\Services\EventLogService\EventLogService;
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
     * @param array $models
     * @param array $raw_meta
     * @return EventLog|mixed
     */
    public function log(string $event, array $models = [], array $raw_meta = []): EventLog
    {
        $logService = resolve(EventLogService::class);

        $meta = array_reduce(array_keys(array_filter($models, static function($model) {
            return $model !== null;
        })), static function($carry, $key) use ($logService, $models) {
            return array_merge($carry, $logService->modelToMeta($key, $models[$key]));
        }, []);

        $data = array_merge([
            'client_type' =>  client_type(),
            'implementation_key' =>  implementation_key(),
        ], $meta, $raw_meta);

        return $this->logs()->create([
            'event' => $event,
            'data' => $data,
            'identity_address' => auth_address()
        ]);
    }

    /**
     * @return MorphMany
     */
    public function logs(): MorphMany
    {
        return $this->morphMany(EventLog::class, 'loggable');
    }
}