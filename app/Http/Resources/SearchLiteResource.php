<?php

namespace App\Http\Resources;

use App\Models\Fund;
use App\Models\Organization;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Model|Organization|Product|Fund $resource
 */
class SearchLiteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     * @throws \Throwable
     */
    public function toArray(Request $request): array
    {
        return array_merge($this->resource->only('id', 'item_type', 'name'));
    }
}
