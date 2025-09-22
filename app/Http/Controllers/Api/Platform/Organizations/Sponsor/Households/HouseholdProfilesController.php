<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor\Households;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Households\HouseholdMembers\IndexHouseholdMembersRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Households\StoreHouseholdMemberRequest;
use App\Http\Resources\HouseholdProfileResource;
use App\Http\Responses\NoContentResponse;
use App\Models\Household;
use App\Models\HouseholdProfile;
use App\Models\Organization;
use App\Searches\Sponsor\HouseholdProfilesSearch;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HouseholdProfilesController extends Controller
{
    /**
     * Retrieves a paginated list of household members associated with the given household.
     *
     * @param IndexHouseholdMembersRequest $request
     * @param Organization $organization
     * @param Household $household
     * @return AnonymousResourceCollection
     */
    public function index(
        IndexHouseholdMembersRequest $request,
        Organization $organization,
        Household $household,
    ): AnonymousResourceCollection {
        $this->authorize('updateHousehold', [$household, $organization]);
        $this->authorize('viewAnyHouseholdProfile', [HouseholdProfile::class, $household, $organization]);

        $search = new HouseholdProfilesSearch([
            'q' => $request->input('q'),
            'organization_id' => $organization->id,
        ], $household->household_profiles());

        return HouseholdProfileResource::queryCollection($search->query(), $request, [
            'organization' => $organization,
        ]);
    }

    /**
     * Stores a household member by associating a profile with a household.
     *
     * @param StoreHouseholdMemberRequest $request
     * @param Organization $organization
     * @param Household $household
     * @return HouseholdProfileResource
     */
    public function store(
        StoreHouseholdMemberRequest $request,
        Organization $organization,
        Household $household
    ): HouseholdProfileResource {
        $this->authorize('updateHousehold', [$household, $organization]);
        $this->authorize('createHouseholdIdentity', [HouseholdProfile::class, $household, $organization]);

        $identity = $organization->findRelatedIdentityOrFail($request->post('identity_id'));
        $profile = $organization->findOrMakeProfile($identity);

        return HouseholdProfileResource::create(HouseholdProfile::firstOrCreate([
            'household_id' => $household->id,
            'profile_id' => $profile->id,
        ]), [
            'organization' => $organization,
        ]);
    }

    /**
     * Removes a member from a household.
     *
     * @param Organization $organization
     * @param Household $household
     * @param HouseholdProfile $householdProfile
     * @return NoContentResponse
     */
    public function destroy(
        Organization $organization,
        Household $household,
        HouseholdProfile $householdProfile,
    ): NoContentResponse {
        $this->authorize('updateHousehold', [$household, $organization]);
        $this->authorize('deleteHouseholdProfile', [$householdProfile, $household, $organization]);

        $householdProfile->delete();

        return new NoContentResponse();
    }
}
