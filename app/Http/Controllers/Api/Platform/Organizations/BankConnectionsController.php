<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\BankConnections\IndexBankConnectionsRequest;
use App\Http\Requests\Api\Platform\Organizations\BankConnections\StoreBankConnectionsRequest;
use App\Http\Requests\Api\Platform\Organizations\BankConnections\UpdateBankConnectionsRequest;
use App\Http\Resources\BankConnectionResource;
use App\Models\BankConnection;
use App\Models\Organization;
use App\Services\BankService\Models\Bank;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BankConnectionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexBankConnectionsRequest $request
     * @param Organization $organization
     * @return AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexBankConnectionsRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', [BankConnection::class, $organization]);

        $query = $organization->bank_connections()->whereNotIn('state', [
            BankConnection::STATE_PENDING,
            BankConnection::STATE_REJECTED,
        ])->orderByDesc('created_at');

        if ($request->has('state')) {
            $query->where('state', $request->input('state'));
        }

        return BankConnectionResource::collection($query->paginate($request->input('per_page')));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreBankConnectionsRequest $request
     * @param Organization $organization
     * @return BankConnectionResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StoreBankConnectionsRequest $request,
        Organization $organization
    ): BankConnectionResource {
        $this->authorize('store', [BankConnection::class, $organization]);

        $bankConnection = $organization->makeBankConnection(
            Bank::find($request->input('bank_id')),
            $organization->findEmployee($request->auth_address()),
            $request->implementation_model()
        );

        return (new BankConnectionResource($bankConnection))->additional([
            'oauth_url' => $bankConnection->getOauthUrl(),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param \App\Models\BankConnection $bankConnection
     * @return BankConnectionResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        BankConnection $bankConnection
    ): BankConnectionResource {
        $this->authorize('view', [$bankConnection, $organization]);

        return new BankConnectionResource($bankConnection);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateBankConnectionsRequest $request
     * @param Organization $organization
     * @param \App\Models\BankConnection $bankConnection
     * @return BankConnectionResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        UpdateBankConnectionsRequest $request,
        Organization $organization,
        BankConnection $bankConnection
    ): BankConnectionResource {
        $this->authorize('update', [$bankConnection, $organization]);

        if ($request->input('state') == BankConnection::STATE_DISABLED) {
            $bankConnection->disable($organization->findEmployee($request->auth_address()));
        }

        return new BankConnectionResource($bankConnection);
    }
}
