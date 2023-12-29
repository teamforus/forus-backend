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
use App\Services\BIConnectionService\Models\BIConnection;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

class BIConnectionController extends Controller
{
    /**
     * @param IndexBIConnectionRequest $request
     * @param Organization $organization
     * @return BIConnectionResource
     */
    public function getActive(
        IndexBIConnectionRequest $request,
        Organization $organization
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

        $connection = BIConnection::makeConnection($organization, $request);

        return new BIConnectionResource($connection);
    }

    /**
     * @param UpdateBIConnectionRequest $request
     * @param Organization $organization
     * @param BIConnection $connection
     * @return BIConnectionResource
     * @noinspection PhpUnused
     */
    public function update(
        UpdateBIConnectionRequest $request,
        Organization $organization,
        BIConnection $connection
    ): BIConnectionResource {
        $this->authorize('update', [
            $connection,
            $organization,
            $request->identityProxy2FAConfirmed(),
        ]);

        return new BIConnectionResource($connection->change($request));
    }

    /**
     * @param ResetTokenBIConnectionRequest $request
     * @param Organization $organization
     * @param BIConnection $connection
     * @return BIConnectionResource
     */
    public function resetToken(
        ResetTokenBIConnectionRequest $request,
        Organization $organization,
        BIConnection $connection
    ): BIConnectionResource {
        $this->authorize('update', [
            $connection,
            $organization,
            $request->identityProxy2FAConfirmed(),
        ]);

        return new BIConnectionResource($connection->resetToken());
    }

    /**
     * @param AvailableTypesBIConnectionRequest $request
     * @param Organization $organization
     * @return JsonResponse
     */
    public function getAvailableDataTypes(
        AvailableTypesBIConnectionRequest $request,
        Organization $organization
    ): JsonResponse {
        $this->authorize('viewAny', [
            BIConnection::class,
            $organization,
            $request->identityProxy2FAConfirmed(),
        ]);

        return new JsonResponse([
            'data' => BIConnection::DATA_TYPES
        ]);
    }
}
