<?php

namespace App\Http\Resources\Tiny;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\MediaResource;
use App\Models\Organization;
use Illuminate\Http\Request;

/**
 * @property-read Organization $resource
 */
class OrganizationTinyResource extends BaseJsonResource
{
    public const array LOAD = [];

    /**
     * @param string|null $append
     * @return array
     */
    public static function load(?string $append = null): array
    {
        $prepend = $append ? "$append." : '';

        return [
            ...parent::load($append),
            ...MediaResource::load("{$prepend}logo"),
        ];
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->resource->only(['id', 'name']),
            'logo' => new MediaResource($this->resource->logo),
        ];
    }
}
