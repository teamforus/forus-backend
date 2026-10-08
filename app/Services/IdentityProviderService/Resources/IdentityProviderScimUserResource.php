<?php

namespace App\Services\IdentityProviderService\Resources;

use App\Http\Resources\BaseJsonResource;
use App\Models\Profile;
use App\Models\ProfileRecord;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Models\IdentityProviderMembership;
use App\Services\IdentityProviderService\Support\IdentityProviderScimDiscovery;
use App\Services\IdentityProviderService\Support\IdentityProviderScimUserPayload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;

/**
 * @property-read IdentityProviderMembership $resource
 */
class IdentityProviderScimUserResource extends BaseJsonResource
{
    public const array LOAD = [
        'connection', 'external_identity', 'identity.primary_email',
    ];

    /**
     * @param IdentityProviderConnection $connection
     * @return array
     */
    public static function loadForConnection(IdentityProviderConnection $connection): array
    {
        return [
            ...static::load(),
            'identity.profiles' => fn (HasMany|Profile $query) =>
                $query->where('organization_id', $connection->organization_id),
            'identity.profiles.profile_records' => fn (HasMany|ProfileRecord $query) => $query
                ->whereHas('record_type', fn (Builder $query) => $query
                    ->whereIn('key', ['given_name', 'family_name', 'birth_date', 'gender']))
                ->with('record_type'),
        ];
    }

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $membership = $this->resource;

        $profile = $membership->identity->profiles
            ->firstWhere('organization_id', $membership->connection->organization_id);

        $records = $profile?->profile_records;

        /** @var ProfileRecord|null $recordGivenName */
        $recordGivenName = $records?->firstWhere('record_type.key', 'given_name');

        /** @var ProfileRecord|null $recordFamilyName */
        $recordFamilyName = $records?->firstWhere('record_type.key', 'family_name');

        /** @var ProfileRecord|null $recordBirthDate */
        $recordBirthDate = $records?->firstWhere('record_type.key', 'birth_date');

        /** @var ProfileRecord|null $recordGender */
        $recordGender = $records?->firstWhere('record_type.key', 'gender');

        $profileAttributes = array_filter([
            'birthDate' => $recordBirthDate?->value,
            'gender' => array_search($recordGender?->value, IdentityProviderScimUserPayload::GENDERS, true) ?: null,
        ]);

        return [
            'schemas' => [
                IdentityProviderScimDiscovery::SCHEMA_USER,
                ...$profileAttributes ? [IdentityProviderScimDiscovery::SCHEMA_FORUS_USER] : [],
            ],
            'id' => $membership->uid,
            'externalId' => $membership->external_identity->object_id,
            'userName' => $membership->scim_user_name,
            'active' => $membership->isProvisioningActive(),
            'name' => [
                'givenName' => $recordGivenName?->value ?? '',
                'familyName' => $recordFamilyName?->value ?? '',
            ],
            ...$profileAttributes ? [IdentityProviderScimDiscovery::SCHEMA_FORUS_USER => $profileAttributes] : [],
            'emails' => [[
                'value' => $membership->identity->primary_email->email,
                'type' => 'work',
                'primary' => true,
            ]],
            'meta' => [
                'resourceType' => 'User',
                'created' => $membership->created_at?->toAtomString(),
                'lastModified' => $membership->updated_at?->toAtomString(),
                'location' => $membership->connection->scimUrl('/Users/' . $membership->uid),
            ],
        ];
    }
}
