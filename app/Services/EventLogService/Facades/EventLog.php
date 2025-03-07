<?php

namespace App\Services\EventLogService\Facades;

use Illuminate\Support\Facades\Facade;

class EventLog extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'forus.event_log';
    }
}
