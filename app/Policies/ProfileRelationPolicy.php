<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\ProfileRelation;
use App\Scopes\Builders\IdentityQuery;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProfileRelationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view profile relations.
     *
     * @param Identity $identity
     * @param Organization $organization
     * @param Identity $sponsorIdentity
     * @return bool
     */
    public function viewProfileRelations(Identity $identity, Organization $organization, Identity $sponsorIdentity): bool
    {
        return
            $this->organizationHasAccessToSponsorIdentity($organization, $sponsorIdentity) &&
            $organization->identityCan($identity, [Permission::VIEW_IDENTITIES, Permission::MANAGE_IDENTITIES], false) &&
            $organization->allow_profiles_relations;
    }

    /**
     * Determine if the user can create a profile relation.
     *
     * @param Identity $identity
     * @param Organization $organization
     * @param Identity $sponsorIdentity
     * @return bool
     */
    public function createProfileRelation(Identity $identity, Organization $organization, Identity $sponsorIdentity): bool
    {
        return
            $this->organizationHasAccessToSponsorIdentity($organization, $sponsorIdentity) &&
            $organization->identityCan($identity, Permission::MANAGE_IDENTITIES) &&
            $organization->allow_profiles_relations;
    }

    /**
     * Determine if the user can update a profile relation.
     *
     * @param Identity $identity
     * @param ProfileRelation $profileRelation
     * @param Organization $organization
     * @param Identity $sponsorIdentity
     * @return bool
     */
    public function updateProfileRelation(
        Identity $identity,
        ProfileRelation $profileRelation,
        Organization $organization,
        Identity $sponsorIdentity,
    ): bool {
        if (!$this->organizationHasAccessToSponsorIdentity($organization, $sponsorIdentity)) {
            return false;
        }

        if (!$organization->identityCan($identity, Permission::MANAGE_IDENTITIES)) {
            return false;
        }

        $belongsToProfile =
            $profileRelation->profile->identity_id === $sponsorIdentity->id &&
            $profileRelation->profile->organization_id === $organization->id;

        $belongsToRelatedProfile =
            $profileRelation->related_profile->identity_id === $sponsorIdentity->id &&
            $profileRelation->related_profile->organization_id === $organization->id;

        return
            ($belongsToProfile || $belongsToRelatedProfile) &&
            $organization->allow_profiles_relations;
    }

    /**
     * Determine if the user can delete a profile relation.
     *
     * @param Identity $identity
     * @param ProfileRelation $profileRelation
     * @param Organization $organization
     * @param Identity $sponsorIdentity
     * @return bool
     */
    public function deleteProfileRelation(
        Identity $identity,
        ProfileRelation $profileRelation,
        Organization $organization,
        Identity $sponsorIdentity,
    ): bool {
        return $this->updateProfileRelation($identity, $profileRelation, $organization, $sponsorIdentity);
    }

    /**
     * Checks if the specified organization has access to the given sponsor identity.
     *
     * @param Organization $organization
     * @param Identity $sponsorIdentity
     * @return bool
     */
    protected function organizationHasAccessToSponsorIdentity(
        Organization $organization,
        Identity $sponsorIdentity,
    ): bool {
        return IdentityQuery::relatedToOrganization(
            Identity::where('id', $sponsorIdentity->id),
            $organization->id,
        )->exists();
    }
}
