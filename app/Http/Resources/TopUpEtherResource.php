<?php

namespace App\Http\Resources;

use App\Models\Fund;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class TopUpResource
 * @property Fund $resource
 * @package App\Http\Resources
 */
class TopUpEtherResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|mixed
     * @throws \Exception
     */
    public function toArray($request)
    {
        return array_only($this->resource->getWallet()->getPublic(), [
            'address'
        ]);
    }
}
