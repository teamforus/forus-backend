<?php

namespace App\Http\Resources;

use App\Http\Resources\Sponsor\SponsorIdentityResource;
use App\Models\Organization;
use App\Models\ProfileRelation;
use Illuminate\Http\Request;

/**
 * @property ProfileRelation $resource
 */
class IdentityRelationResource extends BaseJsonResource
{
    /**
     * @var string[]
     */
    public const array LOAD = [];

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
            ...SponsorIdentityResource::load("{$prepend}related_profile.identity"),
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
