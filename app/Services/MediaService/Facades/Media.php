<?php

namespace App\Services\MediaService\Facades;

use Illuminate\Support\Facades\Facade;

class Media extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'media';
    }
}
