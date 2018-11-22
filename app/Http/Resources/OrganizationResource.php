<?php

namespace App\Http\Resources;

use App\Models\Organization;
use Gate;
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
        /** @var Organization $organization */
        $organization = $this->resource;

        $ownerData = [];

        if (Gate::allows('organizations.update', $organization)) {
            $ownerData = collect($organization)->only([
                'iban', 'btw'
            ])->toArray();
        }

        return collect($organization)->only([
            'id', 'identity_address', 'name', 'email', 'phone', 'kvk'
        ])->merge([
            'permissions' => $organization->identityPermissions(
                auth()->id()
            )->pluck('key'),
            'logo' => new MediaResource($organization->logo),
            'product_categories' => ProductCategoryResource::collection(
                $organization->product_categories
            )
        ])->merge($ownerData)->toArray();
    }
}
