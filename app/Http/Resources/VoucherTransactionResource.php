<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;

class VoucherTransactionResource extends Resource
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
            "organization_id", "product_id", "amount", "created_at",
            "updated_at", "address"
        ])->merge([
            "organization" => collect($this->resource->organization)->only([
                "id", "identity_address", "name", "iban", "email", "phone",
                "kvk", "btw"
            ])->merge([
                'logo' => new MediaCompactResource(
                    $this->resource->organization->logo
                )
            ]),
            "product" => new ProductResource($this->resource->product),
        ])->toArray();
    }
}
