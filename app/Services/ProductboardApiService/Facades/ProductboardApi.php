<?php

namespace App\Services\ProductboardApiService\Facades;

use Illuminate\Support\Facades\Facade;

class ProductboardApi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'productboard';
    }
}
