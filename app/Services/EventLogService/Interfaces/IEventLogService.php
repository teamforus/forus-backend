<?php


namespace App\Services\EventLogService\Interfaces;


use App\Services\EventLogService\Models\EventLog;
use App\Services\EventLogService\Traits\HasLogs;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface IEventLogService
 * @package App\Services\EventLogService\Interfaces
 */
interface IEventLogService
{
    /**
     * @param HasLogs|Model $loggable
     * @param string $action
     * @param array $models
     * @param array $raw_meta
     * @return EventLog
     */
    public function log(
        Model $loggable,
        string $action,
        array $models = [],
        array $raw_meta = []
    ): EventLog;

    /**
     * @param string $type
     * @param $model
     * @return array
     */
    public function modelToMeta(string $type, $model): array;
}