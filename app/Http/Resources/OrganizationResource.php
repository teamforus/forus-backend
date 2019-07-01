<?php

namespace App\Http\Resources;

use App\Models\Organization;
use Gate;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class OrganizationResource
 * @property Organization $resource
 * @package App\Http\Resources
 */
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
        $organization = $this->resource;

        $ownerData = [];
        $website = [];

        if (Gate::allows('organizations.update', $organization)) {
            $ownerData = collect($organization)->only([
                'iban', 'btw', 'phone', 'email', 'email_public',
                'phone_public', 'website_public'
            ])->merge([
                'website' => $organization->website ? : ''
            ])->toArray();
        }

        if ($organization->website_public) {
            $website = ['website' => $organization->website ? : ''];
        }

        return collect($organization)->only([
            'id', 'identity_address', 'name', 'kvk',
            $organization->email_public ? 'email': '',
            $organization->phone_public ? 'phone': ''
        ])->merge([
            'permissions' => $organization->identityPermissions(
                auth()->id()
            )->pluck('key'),
            'logo' => new MediaResource($organization->logo),
            'product_categories' => ProductCategoryResource::collection(
                $organization->product_categories
            )
        ])->merge($website)->merge($ownerData)->toArray();
    }
}
