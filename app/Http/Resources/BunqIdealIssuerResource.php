<?php

namespace App\Http\Resources;

use App\Services\BunqService\Models\BunqIdealIssuer;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class BunqIdealIssuerResource
 * @property BunqIdealIssuer $resource
 * @package App\Http\Resources
 */
class BunqIdealIssuerResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->resource->only([
            'id', 'name', 'bic',
        ]);
    }
}
