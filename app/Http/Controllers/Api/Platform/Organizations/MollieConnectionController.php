<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Events\MollieConnections\MollieConnectionDeleted;
use App\Exceptions\AuthorizationJsonException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\MollieConnections\StoreMollieConnectionRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\MollieConnectionResource;
use App\Models\Organization;
use App\Services\MollieService\Exceptions\MollieApiException;
use App\Services\MollieService\Models\MollieConnection;
use App\Services\MollieService\MollieService;
use App\Traits\ThrottleWithMeta;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

class MollieConnectionController extends Controller
{
    use ThrottleWithMeta;

    /**
     * @param Organization $organization
     * @return MollieConnectionResource
     * @throws AuthorizationException
     */
    public function getConfigured(Organization $organization): MollieConnectionResource
    {
        $this->authorize('viewAny', [MollieConnection::class, $organization]);

        return new MollieConnectionResource($organization->mollie_connection_configured);
    }

    /**
     * @param StoreMollieConnectionRequest $request
     * @param Organization $organization
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function store(
        StoreMollieConnectionRequest $request,
        Organization $organization
    ): JsonResponse {
        $this->authorize('store', [MollieConnection::class, $organization]);

        $mollieService = new MollieService();

        $data = [
            'name' => $request->get('name'),
            'owner' => $request->only([
                'email', 'first_name', 'last_name',
            ]),
            'address' => $request->only([
                'street', 'city', 'postcode', 'country_code',
            ]),
            'profile' => array_merge($request->only('email', 'phone', 'website'), [
                'name' => $request->get('profile_name')
            ]),
        ];

        try {
            $data = $mollieService->createClientLink($data);
        } catch (MollieApiException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Onbekende foutmelding!'], 500);
        }

        MollieConnection::makeNewConnection($organization, array_merge($request->only([
            'email', 'first_name', 'last_name', 'street', 'city', 'postcode',
        ]), [
            'organization_name' => $request->get('name'),
            'country' => $request->get('country_code'),
            'state_code' => $data['state'],
            'profile' => array_merge($request->only('email', 'phone', 'website'), [
                'name' => $request->get('profile_name')
            ]),
        ]));

        return new JsonResponse(Arr::only($data, 'url'));
    }

    /**
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @return JsonResponse
     * @throws AuthorizationException|AuthorizationJsonException
     */
    public function connectMollieAccount(
        BaseFormRequest $request,
        Organization $organization
    ): JsonResponse {
        $this->maxAttempts = Config::get('forus.throttles.mollie.connect.attempts');
        $this->decayMinutes = Config::get('forus.throttles.mollie.connect.decay');

        $this->throttleWithKey('to_many_attempts', $request, 'mollie_connection');
        $this->authorize('connectMollieAccount', [MollieConnection::class, $organization]);

        $mollieService = new MollieService();
        $url = $mollieService->mollieConnect($organization);

        return new JsonResponse(compact('url'));
    }

    /**
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @return MollieConnectionResource|JsonResponse
     * @throws AuthorizationJsonException
     */
    public function fetchMollieAccount(
        BaseFormRequest $request,
        Organization $organization
    ): MollieConnectionResource|JsonResponse {
        $this->maxAttempts = Config::get('forus.throttles.mollie.fetch.attempts');
        $this->decayMinutes = Config::get('forus.throttles.mollie.fetch.decay');

        $this->throttleWithKey('to_many_attempts', $request, 'mollie_connection');
        $this->authorize('fetchMollieAccount', [MollieConnection::class, $organization]);

        try {
            $connection = $organization->mollie_connection_configured->fetchAndUpdateConnection();

            return MollieConnectionResource::create($connection);
        } catch (MollieApiException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * @param Organization $organization
     * @param MollieConnection $connection
     * @return JsonResponse
     */
    public function destroy(
        Organization $organization,
        MollieConnection $connection
    ): JsonResponse {
        $this->authorize('destroy', [$connection, $organization]);

        $mollieService = new MollieService($connection);
        $mollieService->revokeToken();

        MollieConnectionDeleted::dispatch($connection);
        $connection->delete();

        return new JsonResponse();
    }
}
