<?php

namespace App\Services\Forus\Notification\Facades;

use Illuminate\Support\Facades\Facade;

class Notification extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'forus.services.notification';
    }
}
