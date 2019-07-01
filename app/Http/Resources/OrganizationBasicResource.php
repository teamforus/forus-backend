<?php

namespace App\Http\Resources;

use App\Models\Organization;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class OrganizationBasicResource
 * @property Organization $resource
 * @package App\Http\Resources
 */
class OrganizationBasicResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $organization = $this->resource;

        return collect($organization)->only([
            'name',
            $organization->email_public ? 'email': '',
            $organization->phone_public ? 'phone': '',
            $organization->website_public ? 'website': ''
        ])->merge([
            'categories' => $organization->product_categories->pluck(
                'name'
            )->implode(', '),
            'product_categories' => ProductCategoryResource::collection(
                $organization->product_categories
            ),
            'logo' => new MediaCompactResource(
                $organization->logo
            )
        ]);
    }
}
