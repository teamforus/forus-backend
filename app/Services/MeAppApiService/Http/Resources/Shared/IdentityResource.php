<?php

namespace App\Services\MeAppApiService\Http\Resources\Shared;

use App\Http\Resources\BaseJsonResource;
use App\Models\Identity;
use Illuminate\Http\Request;

/**
 * @property-read Identity $resource
 */
class IdentityResource extends BaseJsonResource
{
    public const array LOAD = [
        'primary_email',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'address' => $this->resource->address,
            'email' => $this->resource->email,
        ];
    }
}
