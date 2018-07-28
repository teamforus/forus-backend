<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;

class OrganizationResource extends Resource
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
            'id', 'identity_address', 'name', 'iban', 'email', 'phone',
            'kvk', 'btw'
        ])->merge([
            'product_categories' => ProductCategoryResource::collection(
                $this->resource->product_categories
            )
        ])->toArray();
    }
}
