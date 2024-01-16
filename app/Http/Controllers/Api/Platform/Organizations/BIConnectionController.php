<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\BIConnections\AvailableTypesBIConnectionRequest;
use App\Http\Requests\Api\Platform\Organizations\BIConnections\IndexBIConnectionRequest;
use App\Http\Requests\Api\Platform\Organizations\BIConnections\ResetTokenBIConnectionRequest;
use App\Http\Requests\Api\Platform\Organizations\BIConnections\StoreBIConnectionRequest;
use App\Http\Requests\Api\Platform\Organizations\BIConnections\UpdateBIConnectionRequest;
use App\Http\Resources\BIConnectionResource;
use App\Models\Organization;
use App\Services\BIConnectionService\BIConnectionService;
use App\Services\BIConnectionService\Models\BIConnection;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Throwable;

class BIConnectionController extends Controller
{
    /**
     * @param IndexBIConnectionRequest $request
     * @param Organization $organization
     * @return BIConnectionResource
     */
    public function getActive(
        IndexBIConnectionRequest $request,
        Organization $organization,
    ): BIConnectionResource {
        $this->authorize('viewAny', [
            BIConnection::class,
            $organization,
            $request->identityProxy2FAConfirmed(),
        ]);

        return BIConnectionResource::create($organization->bi_connection);
    }

    /**
     * @param StoreBIConnectionRequest $request
     * @param Organization $organization
     * @return BIConnectionResource
     * @throws AuthorizationException
     */
    public function store(
        StoreBIConnectionRequest $request,
        Organization $organization,
    ): BIConnectionResource {
        $this->authorize('store', [
            BIConnection::class,
            $organization,
            $request->identityProxy2FAConfirmed(),
        ]);

        $connection = BIConnection::createConnection($organization, $request->only([
            'auth_type', 'expiration_period', 'data_types', 'ips',
        ]));

        return BIConnectionResource::create($connection);
    }

    /**
     * @param UpdateBIConnectionRequest $request
     * @param Organization $organization
     * @return BIConnectionResource
     * @noinspection PhpUnused
     */
    public function update(
        UpdateBIConnectionRequest $request,
        Organization $organization,
    ): BIConnectionResource {
        $this->authorize('update', [
            BIConnection::class,
            $organization,
            $request->identityProxy2FAConfirmed(),
        ]);

        $organization->bi_connection->updateConnection($request->only([
            'auth_type', 'expiration_period', 'data_types', 'ips',
        ]));

        return BIConnectionResource::create($organization->bi_connection);
    }

    /**
     * @param ResetTokenBIConnectionRequest $request
     * @param Organization $organization
     * @return BIConnectionResource
     */
    public function resetToken(
        ResetTokenBIConnectionRequest $request,
        Organization $organization,
    ): BIConnectionResource {
        $this->authorize('update', [
            BIConnection::class,
            $organization,
            $request->identityProxy2FAConfirmed(),
        ]);

        return BIConnectionResource::create($organization->bi_connection->resetToken());
    }

    /**
     * @param AvailableTypesBIConnectionRequest $request
     * @param Organization $organization
     * @return JsonResponse
     * @throws Throwable
     */
    public function getAvailableDataTypes(
        AvailableTypesBIConnectionRequest $request,
        Organization $organization,
    ): JsonResponse {
        $this->authorize('viewAny', [
            BIConnection::class,
            $organization,
            $request->identityProxy2FAConfirmed(),
        ]);

        return new JsonResponse([
            'data' => BIConnectionService::create($organization)->getDataTypes(),
        ]);
    }
}
