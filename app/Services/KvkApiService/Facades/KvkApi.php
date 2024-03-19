<?php

namespace App\Services\KvkApiService\Facades;

use Illuminate\Support\Facades\Facade;

class KvkApi extends Facade
{
    /**
     * @return string
     *
     * @psalm-return 'kvk_api'
     */
    protected static function getFacadeAccessor()
    {
        return 'kvk_api';
    }
}
