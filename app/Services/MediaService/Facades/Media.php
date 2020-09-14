<?php

namespace App\Services\MediaService\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class IdentityService
 * @package App\Services\Forus\Identities\Facades
 */
class Media extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'media';
    }
}