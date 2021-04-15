<?php


namespace App\Services\SponsorApiService\Facades;


use Illuminate\Support\Facades\Facade;

class SponsorApiFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'sponsor_api';
    }
}