<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Events\MollieConnections\MollieConnectionCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\MollieConnections\FetchMollieConnectionRequest;
use App\Http\Requests\Api\Platform\Organizations\MollieConnections\OauthMollieConnectionRequest;
use App\Http\Requests\Api\Platform\Organizations\MollieConnections\StoreMollieConnectionRequest;
use App\Http\Requests\Api\Platform\Organizations\MollieConnections\UpdateMollieConnectionRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\MollieConnectionResource;
use App\Models\Organization;
use App\Services\MollieService\Exceptions\MollieException;
use App\Services\MollieService\Models\MollieConnection;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;

class MollieConnectionController extends Controller
{
    /**
     * @param Organization $organization
     * @throws AuthorizationException
     * @return MollieConnectionResource
     */
    public function getActive(Organization $organization): MollieConnectionResource
    {
        $this->authorize('viewAny', [MollieConnection::class, $organization]);

        return MollieConnectionResource::create($organization->mollie_connection);
    }

    /**
     * @param StoreMollieConnectionRequest $request
     * @param Organization $organization
     * @throws AuthorizationException
     * @return JsonResponse
     */
    public function store(
        StoreMollieConnectionRequest $request,
        Organization $organization,
    ): JsonResponse {
        $this->authorize('store', [MollieConnection::class, $organization]);

        $data = $request->only([
            'name', 'country_code', 'profile_name', 'phone', 'website',
            'email', 'first_name', 'last_name', 'street', 'city', 'postcode',
        ]);

        $state = token_generator()->generate(64);

        try {
            $mollieService = MollieConnection::getMollieServiceByForusToken();
            $connectAuthUrl = $mollieService->createClientLink($state, $request->get('name'), [
                'email' => Arr::get($data, 'email'),
                'givenName' => Arr::get($data, 'first_name'),
                'familyName' => Arr::get($data, 'last_name'),
            ], [
                'streetAndNumber' => Arr::get($data, 'street'),
                'postalCode' => Arr::get($data, 'postcode'),
                'city' => Arr::get($data, 'city'),
                'country' => Arr::get($data, 'country_code'),
            ]);
        } catch (MollieException $e) {
            abort(503, $e->getMessage());
        }

        $connection = MollieConnection::makeNewConnection($organization, [
            ...Arr::only($data, [
                'email', 'first_name', 'last_name', 'street', 'city', 'postcode',
            ]),
            'organization_name' => Arr::get($data, 'name'),
            'state_code' => $state,
            'country' => Arr::get($data, 'country_code'),
        ], [
            'name' => Arr::get($data, 'profile_name'),
            ...Arr::only($data, ['email', 'phone', 'website']),
        ]);

        return new JsonResponse([
            'id' => $connection->id,
            'url' => $connectAuthUrl,
        ]);
    }

    /**
     * @param OauthMollieConnectionRequest $request
     * @param Organization $organization
     * @throws MollieException
     * @return JsonResponse
     */
    public function connectOAuth(
        OauthMollieConnectionRequest $request,
        Organization $organization,
    ): JsonResponse {
        $this->authorize('connectMollieAccount', [MollieConnection::class, $organization]);

        $state = token_generator()->generate(64);
        $mollieService = MollieConnection::getMollieServiceByForusToken();
        $connectAuthUrl = $mollieService->mollieConnect($state);

        /** @var MollieConnection $connection */
        $connection = $organization->mollie_connections()->create([
            'state_code' => $state,
        ]);

        Event::dispatch(new MollieConnectionCreated($connection, $request->employee($organization)));

        return new JsonResponse([
            'id' => $connection->id,
            'url' => $connectAuthUrl,
        ]);
    }

    /**
     * @param FetchMollieConnectionRequest $request
     * @param Organization $organization
     * @return MollieConnectionResource
     */
    public function fetchActive(
        FetchMollieConnectionRequest $request,
        Organization $organization,
    ): MollieConnectionResource {
        $this->authorize('fetchMollieAccount', [MollieConnection::class, $organization]);

        try {
            $employee = $request->employee($organization);
            $connection = $organization->mollie_connection->fetchAndUpdateConnection($employee);

            return MollieConnectionResource::create($connection);
        } catch (MollieException $e) {
            abort(503, $e->getMessage());
        }
    }

    /**
     * @param UpdateMollieConnectionRequest $request
     * @param Organization $organization
     * @return MollieConnectionResource
     */
    public function update(
        UpdateMollieConnectionRequest $request,
        Organization $organization,
    ): MollieConnectionResource {
        $this->authorize('update', [MollieConnection::class, $organization]);

        $profileId = $request->input('mollie_connection_profile_id');

        $organization->mollie_connection->changeCurrentProfile(
            $organization->mollie_connection->profiles()->find($profileId),
            $request->employee($organization),
        );

        return MollieConnectionResource::create($organization->mollie_connection);
    }

    /**
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @throws MollieException
     * @return JsonResponse
     */
    public function destroy(BaseFormRequest $request, Organization $organization): JsonResponse
    {
        $this->authorize('destroy', [MollieConnection::class, $organization]);

        $organization->mollie_connection->revoke($request->employee($organization));
        $organization->mollie_connection->delete();

        return new JsonResponse([]);
    }
}
