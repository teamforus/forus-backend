<?php

namespace App\Services\KeyPairGeneratorService\Facades;

use Illuminate\Support\Facades\Facade;

class KeyPairGeneratorService extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'key_pair_generator';
    }
}
