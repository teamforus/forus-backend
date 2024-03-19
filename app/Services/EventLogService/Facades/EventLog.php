<?php

namespace App\Services\EventLogService\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class EventLog
 * @package App\Services\MediaService\Facades
 */
class EventLog extends Facade
{
    /**
     * @return string
     *
     * @psalm-return 'forus.event_log'
     */
    protected static function getFacadeAccessor(): string
    {
        return 'forus.event_log';
    }
}