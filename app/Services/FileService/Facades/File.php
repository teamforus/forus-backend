<?php

namespace App\Services\FileService\Facades;

use Illuminate\Support\Facades\Facade;

class File extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'file';
    }
}
