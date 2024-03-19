<?php

namespace App\Services\TokenGeneratorService\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class TokenGenerator
 * @package App\Services\TokenGeneratorService\Facades
 */
class TokenGenerator extends Facade
{
    /**
     * @return string
     *
     * @psalm-return 'token_generator'
     */
    protected static function getFacadeAccessor(): string
    {
        return 'token_generator';
    }
}
