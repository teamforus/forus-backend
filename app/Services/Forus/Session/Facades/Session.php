<?php

namespace App\Services\Forus\Session\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class IdentityService
 * @package App\Services\Forus\Identities\Facades
 */
class Session extends Facade
{
    /**
     * @return string
     *
     * @psalm-return 'forus.session'
     */
    protected static function getFacadeAccessor()
    {
        return 'forus.session';
    }
}