<?php

namespace App\Services\KvkApiService\Facades;

use Illuminate\Support\Facades\Facade;

class KvkApi extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'kvk_api';
    }
}
