<?php


namespace App\Services\SponsorApiService\Facades;


use Illuminate\Support\Facades\Facade;

class SponsorApi extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'sponsor_api';
    }
}