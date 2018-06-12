<?php

namespace App\Services\ApiRequestService\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class IdentityService
 * @package App\Services\Forus\Identities\Facades
 */
class ApiRequest extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'api_request';
    }
}