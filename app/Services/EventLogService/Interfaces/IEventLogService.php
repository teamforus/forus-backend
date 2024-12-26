<?php


namespace App\Services\EventLogService\Interfaces;

interface IEventLogService
{
    /**
     * @param string $type
     * @param $model
     * @return array
     */
    public function modelToMeta(string $type, $model): array;
}