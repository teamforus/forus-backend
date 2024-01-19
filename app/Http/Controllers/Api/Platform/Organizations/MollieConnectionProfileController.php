<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\MollieConnectionProfiles\StoreMollieConnectionProfileRequest;
use App\Http\Requests\Api\Platform\Organizations\MollieConnectionProfiles\UpdateMollieConnectionProfileRequest;
use App\Http\Resources\MollieConnectionResource;
use App\Models\Organization;
use App\Services\MollieService\Exceptions\MollieException;
use App\Services\MollieService\Models\MollieConnectionProfile;

class MollieConnectionProfileController extends Controller
{
    /**
     * @param StoreMollieConnectionProfileRequest $request
     * @param Organization $organization
     * @return MollieConnectionResource
     */
    public function store(
        StoreMollieConnectionProfileRequest $request,
        Organization $organization,
    ): MollieConnectionResource {
        $this->authorize('store', [MollieConnectionProfile::class, $organization]);

        try {
            $profileData = $request->only(['name', 'email', 'phone', 'website']);
            $profile = $organization->mollie_connection->profiles()->create($profileData);
            $employee = $request->employee($organization);

            $organization->mollie_connection->createProfile($profile);
            $organization->mollie_connection->changeCurrentProfile($profile, $employee);

            return MollieConnectionResource::create($organization->mollie_connection);
        } catch (MollieException $e) {
            abort(503, $e->getMessage());
        }
    }

    /**
     * @param UpdateMollieConnectionProfileRequest $request
     * @param Organization $organization
     * @param MollieConnectionProfile $profile
     * @return MollieConnectionResource
     */
    public function update(
        UpdateMollieConnectionProfileRequest $request,
        Organization $organization,
        MollieConnectionProfile $profile,
    ): MollieConnectionResource {
        $this->authorize('update', [$profile, $organization]);

        try {
            $organization->mollie_connection->syncProfile($profile->updateModel($request->only([
                'name', 'email', 'phone', 'website',
            ])));

            return MollieConnectionResource::create($organization->mollie_connection->refresh());
        } catch (MollieException $e) {
            abort(503, $e->getMessage());
        }
    }
}
