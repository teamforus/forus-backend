<?php

namespace App\Services\MeAppApiService\Http\Resources;

use App\Http\Resources\BaseJsonResource;

abstract class MobileBaseJsonResource extends BaseJsonResource
{
    /**
     * @param mixed $resource
     * @return MobileAnonymousResourceCollection
     */
    protected static function newCollection(mixed $resource): MobileAnonymousResourceCollection
    {
        return new MobileAnonymousResourceCollection($resource, static::class);
    }
}
