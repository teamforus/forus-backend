<?php

namespace App\Http\Resources;

use App\Models\Organization;
use Illuminate\Http\Request;

/**
 * @property Organization $resource
 */
class ProviderResource extends BaseJsonResource
{
    public const array LOAD = [
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
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
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
