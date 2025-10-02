<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor\Identities;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Identities\ProfileRelations\IndexProfileRelationsRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Identities\ProfileRelations\StoreProfileRelationRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Identities\ProfileRelations\UpdateProfileRelationRequest;
use App\Http\Resources\IdentityRelationResource;
use App\Http\Responses\NoContentResponse;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\ProfileRelation;
use App\Scopes\Builders\ProfileRelationQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProfileRelationsController extends Controller
{
    /**
     * Retrieve and return a collection of profile relations for a given identity.
     *
     * @param IndexProfileRelationsRequest $request
     * @param Organization $organization
     * @param Identity $identity
     * @return AnonymousResourceCollection
     */
    public function index(
        IndexProfileRelationsRequest $request,
        Organization $organization,
        Identity $identity,
    ): AnonymousResourceCollection {
        $this->authorize('viewProfileRelations', [ProfileRelation::class, $organization, $identity]);

        $query = ProfileRelationQuery::filterIdentityRelations(ProfileRelation::query(), $identity, $organization);

        return IdentityRelationResource::queryCollection($query, $request, [
            'organization' => $organization,
        ]);
    }

    /**
     * @param StoreProfileRelationRequest $request
     * @param Organization $organization
     * @param Identity $identity
     * @return IdentityRelationResource
     */
    public function store(
        StoreProfileRelationRequest $request,
        Organization $organization,
        Identity $identity
    ): IdentityRelationResource {
        $this->authorize('createProfileRelation', [ProfileRelation::class, $organization, $identity]);

        $profile = $organization->findOrMakeProfile($identity);

        $relatedIdentity = $organization->findRelatedIdentityOrFail($request->post('related_identity_id'));
        $relatedProfile = $organization->findOrMakeProfile($relatedIdentity);

        $relation = $profile->profile_relations()->firstOrCreate([
            ...$request->onlyValidated([
                'type', 'subtype', 'living_together',
            ]),
            'related_profile_id' => $relatedProfile->id,
        ]);

        return IdentityRelationResource::create($relation, [
            'organization' => $organization,
        ]);
    }

    /**
     * @param UpdateProfileRelationRequest $request
     * @param Organization $organization
     * @param Identity $identity
     * @param ProfileRelation $profileRelation
     * @return IdentityRelationResource
     */
    public function update(
        UpdateProfileRelationRequest $request,
        Organization $organization,
        Identity $identity,
        ProfileRelation $profileRelation
    ): IdentityRelationResource {
        $this->authorize('updateProfileRelation', [$profileRelation, $organization, $identity]);

        $profileRelation->update($request->onlyValidated([
            'type', 'subtype', 'living_together',
        ]));

        return IdentityRelationResource::create($profileRelation, [
            'organization' => $organization,
        ]);
    }

    /**
     * @param Organization $organization
     * @param Identity $identity
     * @param ProfileRelation $profileRelation
     * @return NoContentResponse
     */
    public function destroy(
        Organization $organization,
        Identity $identity,
        ProfileRelation $profileRelation
    ): NoContentResponse {
        $this->authorize('deleteProfileRelation', [$profileRelation, $organization, $identity]);

        $profileRelation->delete();

        return new NoContentResponse();
    }
}
