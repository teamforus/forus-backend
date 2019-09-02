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

        if (Gate::allows('organizations.update', $organization)) {
            $ownerData = collect($organization)->only([
                'iban', 'btw', 'phone', 'email', 'website', 'email_public',
                'phone_public', 'website_public'
            ])->toArray();
        }

        return array_merge(collect($organization)->only([
            'id', 'identity_address', 'name', 'kvk', 'business_type_id',
            $organization->email_public ? 'email': '',
            $organization->phone_public ? 'phone': '',
            $organization->website_public ? 'website': '',
        ])->toArray(), $ownerData, [
            'logo' => new MediaResource($organization->logo),
            'business_type' => new BusinessTypeResource(
                $organization->business_type
            ),
            'permissions' => $organization->identityPermissions(
                auth()->id()
            )->pluck('key'),
        ]);
    }
}
