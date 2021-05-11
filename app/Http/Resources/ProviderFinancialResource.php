<?php

namespace App\Http\Resources;

use App\Models\Organization;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class ProviderFinancialResource
 * @property Organization $resource
 * @package App\Http\Resources
 */
class ProviderFinancialResource extends JsonResource
{
    public static $load = [
        'logo',
        'business_type.translations',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return array_merge($this->resource->only([
            'highest_transaction', 'nr_transactions', 'total_spent',
        ]), [
            'provider' => new OrganizationBasicResource($this->resource),
        ]);
    }
}
