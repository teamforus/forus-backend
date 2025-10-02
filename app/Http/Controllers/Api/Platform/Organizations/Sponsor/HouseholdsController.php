<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Households\HouseholdMembers\UpdateHouseholdRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Households\IndexHouseholdsRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Households\StoreHouseholdRequest;
use App\Http\Resources\HouseholdResource;
use App\Http\Responses\NoContentResponse;
use App\Models\Household;
use App\Models\Organization;
use App\Searches\Sponsor\HouseholdSearch;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HouseholdsController extends Controller
{
    /**
     * Retrieves a paginated list of households associated with the given organization.
     *
     * @param IndexHouseholdsRequest $request
     * @param Organization $organization
     * @return AnonymousResourceCollection
     */
    public function index(IndexHouseholdsRequest $request, Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('viewAnyHousehold', [Household::class, $organization]);

        $search = new HouseholdSearch([
            ...$request->only([
                'q', 'living_arrangement', 'order_by', 'order_dir', 'fund_id',
            ]),
            'organization_id' => $organization->id,
        ], Household::where('organization_id', $organization->id));

        return HouseholdResource::queryCollection($search->query(), $request);
    }

    /**
     * Creates a new household within the specified organization.
     *
     * @param StoreHouseholdRequest $request
     * @param Organization $organization
     * @return HouseholdResource
     */
    public function store(StoreHouseholdRequest $request, Organization $organization): HouseholdResource
    {
        $this->authorize('createHousehold', [Household::class, $organization]);

        return HouseholdResource::create(Household::create([
            ...array_only($request->validated(), [
                'living_arrangement', 'uid', 'count_people', 'count_minors', 'count_adults',
                'city', 'street', 'house_nr', 'house_nr_addition', 'postal_code', 'neighborhood_name', 'municipality_name',
            ]),
            'organization_id' => $organization->id,
        ]));
    }

    /**
     * Retrieves and returns a formatted representation of a household resource.
     *
     * @param Organization $organization
     * @param Household $household
     * @return HouseholdResource
     */
    public function show(Organization $organization, Household $household): HouseholdResource
    {
        $this->authorize('viewHousehold', [$household, $organization]);

        return HouseholdResource::create($household);
    }

    /**
     * Updates a household with the provided request data.
     *
     * @param UpdateHouseholdRequest $request
     * @param Organization $organization
     * @param Household $household
     * @return HouseholdResource
     */
    public function update(
        UpdateHouseholdRequest $request,
        Organization $organization,
        Household $household,
    ): HouseholdResource {
        $this->authorize('updateHousehold', [$household, $organization]);

        $household->update(array_only($request->validated(), [
            'living_arrangement', 'uid', 'count_people', 'count_minors', 'count_adults',
            'city', 'street', 'house_nr', 'house_nr_addition', 'postal_code', 'neighborhood_name', 'municipality_name',
        ]));

        return HouseholdResource::create($household);
    }

    /**
     * Deletes a household and authorizes the action based on organization and household.
     *
     * @param Organization $organization
     * @param Household $household
     * @return NoContentResponse
     */
    public function destroy(Organization $organization, Household $household): NoContentResponse
    {
        $this->authorize('deleteHousehold', [$household, $organization]);

        $household->delete();

        return new NoContentResponse();
    }
}
