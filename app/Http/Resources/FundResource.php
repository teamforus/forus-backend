<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;

class FundResource extends Resource
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
            'id', 'name', 'start_date', 'end_date'
        ])->merge([
            'product_categories' => ProductCategoryResource::collection(
                $this->resource->product_categories
            )
        ])->toArray();
    }
}
