<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\MollieConnectionProfiles\StoreMollieConnectionProfileRequest;
use App\Http\Requests\Api\Platform\Organizations\MollieConnectionProfiles\UpdateMollieConnectionProfileRequest;
use App\Http\Resources\MollieConnectionResource;
use App\Models\Organization;
use App\Services\MollieService\Exceptions\MollieApiException;
use App\Services\MollieService\Models\MollieConnection;
use App\Services\MollieService\Models\MollieConnectionProfile;
use Illuminate\Http\JsonResponse;

class MollieConnectionProfileController extends Controller
{

    public function store(
        StoreMollieConnectionProfileRequest $request,
        Organization $organization,
        MollieConnection $connection
    ) {
        $this->authorize('store', [MollieConnectionProfile::class, $connection, $organization]);

        $profile = $connection->profiles()->create(
            $request->only('name', 'email', 'phone', 'website')
        );

        try {
            $connection->createProfile($profile);

            return MollieConnectionResource::create($connection);
        } catch (MollieApiException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        }

    }

    /**
     * @param UpdateMollieConnectionProfileRequest $request
     * @param Organization $organization
     * @param MollieConnection $connection
     * @param MollieConnectionProfile $profile
     * @return MollieConnectionResource|JsonResponse
     */
    public function update(
        UpdateMollieConnectionProfileRequest $request,
        Organization $organization,
        MollieConnection $connection,
        MollieConnectionProfile $profile
    ) {
        $this->authorize('update', [$profile, $connection, $organization]);

        $profile->update($request->only('name', 'email', 'phone', 'website'));

        try {
            $profile->mollie_id
                ? $connection->updateProfile($profile)
                : $connection->createProfile($profile);

            return MollieConnectionResource::create($connection->refresh());
        } catch (MollieApiException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        }
    }
}
