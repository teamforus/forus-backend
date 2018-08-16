<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;

class ProductResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return collect($this->resource)->only([
            'id', 'name', 'description', 'price', 'old_price',
            'total_amount', 'sold_amount', 'product_category_id',
            'organization_id'
        ])->merge([
            'photo' => new MediaResource($this->resource->photo),
            'product_category' => new ProductCategoryResource(
                $this->resource->product_category
            ),
            'funds' => "Lorem, Ipsum"
        ])->toArray();
    }
}
