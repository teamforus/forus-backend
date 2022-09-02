<?php

namespace App\Http\Resources;

use App\Models\Organization;

/**
 * Class OrganizationBasicResource
 * @property Organization $resource
 * @package App\Http\Resources
 */
class ProviderResource extends BaseJsonResource
{
    public const LOAD = [
        'business_type.translations',
        'offices.photo.presets',
        'offices.organization.business_type.translations',
        'offices.organization.logo.presets',
        'offices.schedules',
        'logo.presets',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $organization = $this->resource;

        $fields = array_merge([
            'id', 'name', 'description', 'business_type_id',
        ], array_filter([
            $organization->email_public ? 'email': null,
            $organization->phone_public ? 'phone': null,
            $organization->website_public ? 'website': null,
        ]));

        return array_merge($organization->only($fields), [
            'description_html' => $organization->description_html,
            'business_type' => new BusinessTypeResource($organization->business_type),
            'offices' => OfficeResource::collection($organization->offices),
            'logo' => new MediaCompactResource($organization->logo),
        ]);
    }
}
