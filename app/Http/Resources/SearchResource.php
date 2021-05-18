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
class SearchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $hasPrice = $this->resource instanceof Product && !is_null($this->resource->price);

        return array_merge($this->resource->only('id', 'item_type'), [
            'name' => e($this->resource->name),
            'description_text' => $this->resource->description_text,
            'price' => $hasPrice ? currency_format($this->resource->price) : null,
            'price_locale' => $hasPrice ? $this->resource->price_locale : null,
        ]);
    }
}
