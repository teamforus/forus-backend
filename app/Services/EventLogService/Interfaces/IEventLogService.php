<?php


namespace App\Services\EventLogService\Interfaces;


use App\Services\EventLogService\Models\EventLog;
use App\Services\EventLogService\Traits\HasLogs;

interface IEventLogService
{
    /**
     * @param HasLogs $loggable
     * @param string $action
     * @param array $models
     * @param array $raw_meta
     * @return EventLog
     */
    public function log($loggable, string $action, array $models = [], array $raw_meta = []): EventLog;


    /**
     * @param string $type
     * @param $model
     * @return array
     */
    public function modelToMeta(string $type, $model): array;
}