<?php

namespace App\Http\Resources;

use App\Models\Household;
use Illuminate\Http\Request;

/**
 * @property Household $resource
 */
class HouseholdResource extends BaseJsonResource
{
    /**
     * @var string[]
     */
    public const array LOAD = [
        'household_profiles',
    ];

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'uid' => $this->resource->uid,
            'organization_id' => $this->resource->organization_id,
            'living_arrangement' => $this->resource->living_arrangement,
            'count_people' => $this->resource->count_people,
            'count_minors' => $this->resource->count_minors,
            'count_adults' => $this->resource->count_adults,
            'city' => $this->resource->city,
            'street' => $this->resource->street,
            'house_nr' => $this->resource->house_nr,
            'house_nr_addition' => $this->resource->house_nr_addition,
            'postal_code' => $this->resource->postal_code,
            'neighborhood_name' => $this->resource->neighborhood_name,
            'municipality_name' => $this->resource->municipality_name,
            'members_count' => $this->resource->household_profiles->count(),
            'removable' => $this->resource->household_profiles->isEmpty(),
            'created_at' => $this->resource->created_at?->toAtomString(),
            'updated_at' => $this->resource->updated_at?->toAtomString(),
        ];
    }
}
