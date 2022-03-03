<?php

namespace App\Services\ProductboardApiService\Facades;

use Illuminate\Support\Facades\Facade;

class ProductboardApi extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'productboard_api';
    }
}
