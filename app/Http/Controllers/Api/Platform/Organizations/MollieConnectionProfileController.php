<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\MollieConnectionProfiles\ChangeMollieConnectionCurrentProfileRequest;
use App\Http\Requests\Api\Platform\Organizations\MollieConnectionProfiles\StoreMollieConnectionProfileRequest;
use App\Http\Requests\Api\Platform\Organizations\MollieConnectionProfiles\UpdateMollieConnectionProfileRequest;
use App\Http\Resources\MollieConnectionResource;
use App\Models\Organization;
use App\Services\MollieService\Exceptions\MollieException;
use App\Services\MollieService\Models\MollieConnection;
use App\Services\MollieService\Models\MollieConnectionProfile;

class MollieConnectionProfileController extends Controller
{
    /**
     * @param StoreMollieConnectionProfileRequest $request
     * @param Organization $organization
     * @param MollieConnection $connection
     * @return MollieConnectionResource
     */
    public function store(
        StoreMollieConnectionProfileRequest $request,
        Organization $organization,
        MollieConnection $connection,
    ): MollieConnectionResource {
        $this->authorize('store', [MollieConnectionProfile::class, $connection, $organization]);

        try {
            $connection->createProfile($connection->profiles()->create($request->only([
                'name', 'email', 'phone', 'website',
            ])));

            return MollieConnectionResource::create($connection);
        } catch (MollieException $e) {
            abort(503, $e->getMessage());
        }
    }

    /**
     * @param UpdateMollieConnectionProfileRequest $request
     * @param Organization $organization
     * @param MollieConnection $connection
     * @param MollieConnectionProfile $profile
     * @return MollieConnectionResource
     */
    public function update(
        UpdateMollieConnectionProfileRequest $request,
        Organization $organization,
        MollieConnection $connection,
        MollieConnectionProfile $profile
    ): MollieConnectionResource {
        $this->authorize('update', [$profile, $connection, $organization]);

        try {
            $connection->syncProfile($profile->updateModel($request->only([
                'name', 'email', 'phone', 'website',
            ])));

            return MollieConnectionResource::create($connection->refresh());
        } catch (MollieException $e) {
            abort(503, $e->getMessage());
        }
    }

    /**
     * @param ChangeMollieConnectionCurrentProfileRequest $request
     * @param Organization $organization
     * @param MollieConnection $connection
     * @param MollieConnectionProfile $profile
     * @return MollieConnectionResource
     */
    public function setCurrentProfile(
        ChangeMollieConnectionCurrentProfileRequest $request,
        Organization $organization,
        MollieConnection $connection,
        MollieConnectionProfile $profile
    ): MollieConnectionResource {
        $this->authorize('update', [$profile, $connection, $organization]);

        return MollieConnectionResource::create(
            $connection->changeCurrentProfile($profile, $request->employee($organization))
        );
    }
}
