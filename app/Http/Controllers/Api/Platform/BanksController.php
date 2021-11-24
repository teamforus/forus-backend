<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Banks\IndexBanksRequest;
use App\Services\BankService\Models\Bank;
use App\Services\BankService\Resources\BankResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BanksController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexBanksRequest $request
     * @return AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(IndexBanksRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Bank::class);

        return BankResource::collection(Bank::paginate($request->input('per_page')));
    }

    /**
     * Display the specified resource.
     *
     * @param Bank $bank
     * @return BankResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Bank $bank): BankResource
    {
        $this->authorize('show', $bank);

        return new BankResource($bank);
    }
}