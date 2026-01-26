<?php

namespace App\Http\Resources;

use App\Http\Resources\Sponsor\SponsorIdentityResource;
use App\Models\ProfileRelation;
use App\Services\MollieService\Objects\Organization;
use Illuminate\Http\Request;

/**
 * @property Organization $organization
 * @property ProfileRelation $resource
 */
class IdentityRelationResource extends BaseJsonResource
{
    public const array LOAD_NESTED = [
        'profile.identity' => SponsorIdentityResource::class,
        'related_profile.identity' => SponsorIdentityResource::class,
    ];

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->resource->only([
                'id', 'type', 'type_locale', 'subtype', 'subtype_locale', 'living_together', 'living_together_locale',
                'profile_id', 'related_profile_id',
            ]),
            'identity' => SponsorIdentityResource::create($this->resource->profile->identity, [
                'detailed' => true,
                'organization' => $this->organization,
            ]),
            'identity_id' => $this->resource->profile->identity_id,
            'related_identity' => SponsorIdentityResource::create($this->resource->related_profile->identity, [
                'detailed' => true,
                'organization' => $this->organization,
            ]),
            'related_identity_id' => $this->resource->related_profile->identity_id,
            $this->makeTimestamps($this->resource->only([
                'created_at', 'updated_at',
            ])),
        ];
    }
}
