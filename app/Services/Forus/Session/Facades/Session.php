<?php

namespace App\Services\Forus\Session\Facades;

use Illuminate\Support\Facades\Facade;

class Session extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'forus.session';
    }
}
