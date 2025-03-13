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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return AnonymousResourceCollection
     */
    public function index(
        IndexBankConnectionsRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', [BankConnection::class, $organization]);

        $query = $organization->bank_connections()->whereNotIn('state', [
            BankConnection::STATE_ERROR,
            BankConnection::STATE_PENDING,
            BankConnection::STATE_REJECTED,
        ])->orderByDesc('created_at');

        if ($request->has('state')) {
            $query->where('state', $request->input('state'));
        }

        return BankConnectionResource::queryCollection($query, $request);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreBankConnectionsRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return BankConnectionResource
     */
    public function store(
        StoreBankConnectionsRequest $request,
        Organization $organization
    ): BankConnectionResource {
        $this->authorize('store', [BankConnection::class, $organization]);

        $bank = Bank::find($request->input('bank_id'));
        $employee = $request->employee($organization);
        $connection = $organization->makeBankConnection($bank, $employee, $request->implementation());
        $auth_url = $connection->makeOauthUrl();

        $connection->updateModel(is_string($auth_url) ? [
            'auth_url' => $auth_url,
        ] : [
            'state' => $connection::STATE_ERROR,
        ]);

        return BankConnectionResource::create($connection);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param \App\Models\BankConnection $bankConnection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return BankConnectionResource
     */
    public function show(
        Organization $organization,
        BankConnection $bankConnection
    ): BankConnectionResource {
        $this->authorize('view', [$bankConnection, $organization]);

        return BankConnectionResource::create($bankConnection);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateBankConnectionsRequest $request
     * @param Organization $organization
     * @param \App\Models\BankConnection $bankConnection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return BankConnectionResource
     */
    public function update(
        UpdateBankConnectionsRequest $request,
        Organization $organization,
        BankConnection $bankConnection
    ): BankConnectionResource {
        $this->authorize('update', [$bankConnection, $organization]);

        $employee = $organization->findEmployee($request->auth_address());
        $bank_connection_account_id = $request->input('bank_connection_account_id');

        $activeConnection = $organization->bank_connection_active;
        $isActiveConnection = $activeConnection && ($activeConnection->id == $bankConnection->id);

        if ($request->input('state') == BankConnection::STATE_DISABLED) {
            $bankConnection->disable($employee);
        }

        if ($isActiveConnection && $bank_connection_account_id) {
            $bankConnection->switchBankConnectionAccount($bank_connection_account_id, $employee);
        }

        return BankConnectionResource::create($bankConnection);
    }
}
