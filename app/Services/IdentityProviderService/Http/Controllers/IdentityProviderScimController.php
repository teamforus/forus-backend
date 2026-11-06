<?php

namespace App\Services\IdentityProviderService\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\NoContentResponse;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderScimException;
use App\Services\IdentityProviderService\Http\Requests\IndexIdentityProviderScimUsersRequest;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Models\IdentityProviderMembership;
use App\Services\IdentityProviderService\Queries\IdentityProviderMembershipQuery;
use App\Services\IdentityProviderService\Resources\IdentityProviderScimUserResource;
use App\Services\IdentityProviderService\Responses\IdentityProviderScimResponse;
use App\Services\IdentityProviderService\Services\IdentityProviderProvisioningService;
use App\Services\IdentityProviderService\Support\IdentityProviderScimDiscovery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Throwable;

class IdentityProviderScimController extends Controller
{
    /**
     * @param IdentityProviderProvisioningService $provisioningService
     */
    public function __construct(protected IdentityProviderProvisioningService $provisioningService)
    {
    }

    /**
     * @param IndexIdentityProviderScimUsersRequest $request
     * @param IdentityProviderConnection $connection
     * @return JsonResponse
     */
    public function index(
        IndexIdentityProviderScimUsersRequest $request,
        IdentityProviderConnection $connection,
    ): JsonResponse {
        $startIndex = max(1, (int) $request->input('startIndex', 1));
        $maxResults = max(1, (int) Config::get('identity_providers.scim.max_results', 100));
        $count = min($maxResults, max(0, (int) $request->input('count', $maxResults)));
        $accountType = $request->attributes->get('scim_account_type');

        $membership = IdentityProviderMembershipQuery::forScimConnection($connection, $accountType)
            ->with(IdentityProviderScimUserResource::loadForConnection($connection, $accountType));

        $query = IdentityProviderMembershipQuery::whereScimFilter($membership, $request->scimFilter());
        $total = $query->count();

        $resources = $count === 0 ? [] : $query
            ->orderBy('id')
            ->offset($startIndex - 1)
            ->limit($count)
            ->get()
            ->map(fn (IdentityProviderMembership $membership) =>
                IdentityProviderScimUserResource::make($membership)->resolve($request))
            ->all();

        return IdentityProviderScimResponse::listing($resources, $total, $startIndex);
    }

    /**
     * @param Request $request
     * @param IdentityProviderConnection $connection
     * @param string $uid
     * @return JsonResponse
     */
    public function show(Request $request, IdentityProviderConnection $connection, string $uid): JsonResponse
    {
        $accountType = $request->attributes->get('scim_account_type');

        $membership = IdentityProviderMembershipQuery::forScimConnection($connection, $accountType)
            ->with(IdentityProviderScimUserResource::loadForConnection($connection, $accountType))
            ->where('uid', $uid)->firstOrFail();

        return IdentityProviderScimResponse::json(
            IdentityProviderScimUserResource::make($membership)->resolve($request),
        );
    }

    /**
     * @param Request $request
     * @param IdentityProviderConnection $connection
     * @throws Throwable
     * @return JsonResponse
     */
    public function store(Request $request, IdentityProviderConnection $connection): JsonResponse
    {
        $accountType = $request->attributes->get('scim_account_type');
        $membership = $this->provisioningService->create($connection, $request->all(), $accountType);

        $membership->load(IdentityProviderScimUserResource::loadForConnection($connection, $membership->account_type));

        return IdentityProviderScimResponse::json(
            IdentityProviderScimUserResource::make($membership)->resolve($request),
            201,
            ['Location' => $connection->scimUrl('/Users/' . $membership->uid)],
        );
    }

    /**
     * @param Request $request
     * @param IdentityProviderConnection $connection
     * @param string $uid
     * @throws Throwable
     * @return JsonResponse
     */
    public function update(Request $request, IdentityProviderConnection $connection, string $uid): JsonResponse
    {
        $membership = $this->provisioningService->update(
            $connection,
            $uid,
            $request->all(),
            $request->attributes->get('scim_account_type'),
            $request->isMethod('PATCH'),
        );

        $membership->load(IdentityProviderScimUserResource::loadForConnection($connection, $membership->account_type));

        return IdentityProviderScimResponse::json(IdentityProviderScimUserResource::make($membership)->resolve($request));
    }

    /**
     * @param Request $request
     * @param IdentityProviderConnection $connection
     * @param string $uid
     * @throws Throwable
     * @return NoContentResponse
     */
    public function destroy(Request $request, IdentityProviderConnection $connection, string $uid): NoContentResponse
    {
        $this->provisioningService->delete($connection, $uid, $request->attributes->get('scim_account_type'));

        return (new NoContentResponse())->header('Cache-Control', 'no-store');
    }

    /**
     * @return JsonResponse
     */
    public function serviceProviderConfig(): JsonResponse
    {
        return IdentityProviderScimResponse::json(IdentityProviderScimDiscovery::serviceProviderConfig());
    }

    /**
     * @param Request $request
     * @param IdentityProviderConnection $connection
     * @param string|null $schema
     * @throws IdentityProviderScimException
     * @return JsonResponse
     */
    public function schemas(
        Request $request,
        IdentityProviderConnection $connection,
        ?string $schema = null,
    ): JsonResponse {
        $accountType = $request->attributes->get('scim_account_type');

        $resources = [
            IdentityProviderScimDiscovery::SCHEMA_USER =>
                IdentityProviderScimDiscovery::schemaUser($connection, $accountType),
        ];

        if ($connection->usesScimProfiles($accountType)) {
            $resources[IdentityProviderScimDiscovery::SCHEMA_FORUS_USER] =
                IdentityProviderScimDiscovery::schemaForusUser($connection);
        }

        if ($schema !== null && !isset($resources[$schema])) {
            throw new IdentityProviderScimException(__('identity_provider.scim.not_found'), 404);
        }

        return $schema === null
            ? IdentityProviderScimResponse::listing(array_values($resources), count($resources))
            : IdentityProviderScimResponse::json($resources[$schema]);
    }

    /**
     * @param Request $request
     * @param IdentityProviderConnection $connection
     * @param string|null $type
     * @throws IdentityProviderScimException
     * @return JsonResponse
     */
    public function resourceTypes(
        Request $request,
        IdentityProviderConnection $connection,
        ?string $type = null,
    ): JsonResponse {
        if ($type !== null && $type !== 'User') {
            throw new IdentityProviderScimException(__('identity_provider.scim.not_found'), 404);
        }

        $resource = IdentityProviderScimDiscovery::resourceTypeUser(
            $connection,
            $request->attributes->get('scim_account_type'),
        );

        return $type === null
            ? IdentityProviderScimResponse::listing([$resource], 1)
            : IdentityProviderScimResponse::json($resource);
    }
}
