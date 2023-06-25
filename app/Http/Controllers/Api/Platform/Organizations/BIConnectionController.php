<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\BIConnection\IndexBIConnectionRequest;
use App\Http\Requests\Api\Platform\Organizations\BIConnection\StoreBIConnectionRequest;
use App\Http\Requests\Api\Platform\Organizations\BIConnection\RecreateBIConnectionRequest;
use App\Http\Requests\Api\Platform\Organizations\BIConnection\UpdateBIConnectionRequest;
use App\Http\Resources\BIConnectionResource;
use App\Models\BIConnection;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BIConnectionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexBIConnectionRequest $request
     * @param Organization $organization
     * @return AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexBIConnectionRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', [BIConnection::class, $organization]);

        $query = BIConnection::query()->where('organization_id', $organization->id);

        return BIConnectionResource::queryCollection($query, $request);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreBIConnectionRequest $request
     * @param Organization $organization
     * @return BIConnectionResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StoreBIConnectionRequest $request,
        Organization $organization
    ): BIConnectionResource {
        $this->authorize('store', [BIConnection::class, $organization]);

        $connection = BIConnection::create([
            'token' => bin2hex(openssl_random_pseudo_bytes(32)),
            'auth_type' => $request->get('auth_type'),
            'organization_id' => $organization->id,
        ]);

        $connection->log($connection::EVENT_CREATED);

        return BIConnectionResource::create($connection);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param BIConnection $connection
     * @return BIConnectionResource
     */
    public function show(
        Organization $organization,
        BIConnection $connection
    ): BIConnectionResource {
        $this->authorize('view', [$connection, $organization]);

        return BIConnectionResource::create($connection);
    }

    /**
     * @param UpdateBIConnectionRequest $request
     * @param Organization $organization
     * @param BIConnection $connection
     * @return BIConnectionResource
     */
    public function update(
        UpdateBIConnectionRequest $request,
        Organization $organization,
        BIConnection $connection
    ): BIConnectionResource {
        $this->authorize('update', [$connection, $organization]);

        $connection->update($request->only(['auth_type']));
        $connection->log($connection::EVENT_UPDATED);

        return BIConnectionResource::create($connection);
    }

    /**
     * @param RecreateBIConnectionRequest $request
     * @param Organization $organization
     * @return BIConnectionResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function recreate(
        RecreateBIConnectionRequest $request,
        Organization $organization
    ): BIConnectionResource {
        $this->authorize('recreate', [BIConnection::class, $organization]);

        $organization->bIConnections()->delete();

        $connection = BIConnection::create([
            'token' => bin2hex(openssl_random_pseudo_bytes(32)),
            'auth_type' => $request->get('auth_type'),
            'organization_id' => $organization->id,
        ]);

        $connection->log($connection::EVENT_RECREATED);

        return BIConnectionResource::create($connection);
    }
}
