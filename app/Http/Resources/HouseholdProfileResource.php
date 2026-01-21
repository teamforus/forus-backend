<?php

namespace App\Http\Resources;

use App\Http\Resources\Sponsor\SponsorIdentityResource;
use App\Models\HouseholdProfile;
use App\Models\Organization;
use Illuminate\Http\Request;

/**
 * @property HouseholdProfile $resource
 */
class HouseholdProfileResource extends BaseJsonResource
{
    protected ?Organization $organization = null;

    /**
     * @param string|null $append
     * @return array
     */
    public static function load(?string $append = null): array
    {
        $prepend = $append ? "$append." : '';

        return [
            ...parent::load($append),
            ...SponsorIdentityResource::load("{$prepend}profile.identity"),
        ];
    }

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->resource->only([
                'id', 'household_id', 'profile_id',
            ]),
            'identity' => SponsorIdentityResource::create($this->resource?->profile?->identity, [
                'detailed' => true,
                'organization' => $this->organization,
            ]),
            ...$this->makeTimestamps($this->resource->only([
                'created_at', 'updated_at',
            ])),
        ];
    }
}
