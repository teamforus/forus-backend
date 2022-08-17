<?php

namespace App\Services\MediaService\Traits;

use App\Services\MediaService\MediaService;

trait UsesMediaService
{
    protected function mediaService(): MediaService
    {
        return resolve('media');
    }
}