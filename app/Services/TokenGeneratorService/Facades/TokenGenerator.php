<?php

namespace App\Services\TokenGeneratorService\Facades;

use Illuminate\Support\Facades\Facade;

class TokenGenerator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'token_generator';
    }
}
