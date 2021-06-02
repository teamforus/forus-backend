<?php

namespace App\Http\Resources;

use App\Models\Fund;
use App\Models\Organization;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class SearchResource
 * @property Model|Organization|Product|Fund $resource
 * @package App\Http\Resources
 */
class SearchLiteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     * @throws \Throwable
     */
    public function toArray($request): array
    {
        return array_merge($this->resource->only('id', 'item_type', 'name'));
    }
}
