<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Provider\FundUnsubscribes\IndexFundUnsubscribeRequest;
use App\Http\Requests\Api\Platform\Organizations\Provider\FundUnsubscribes\StoreFundUnsubscribeRequest;
use App\Http\Requests\Api\Platform\Organizations\Provider\FundUnsubscribes\UpdateFundUnsubscribeRequest;
use App\Http\Resources\FundUnsubscribeResource;
use App\Models\FundProvider;
use App\Models\FundUnsubscribe;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FundUnsubscribeController extends Controller
{
    /**
     * Show fund unsubscribe list.
     *
     * @param IndexFundUnsubscribeRequest $request
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexFundUnsubscribeRequest $request,
        Organization $organization,
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAnyProvider', [FundProvider::class, $organization]);

        $query = FundUnsubscribe::searchProvider(
            $organization, $request->only('state', 'q', 'fund_id', 'from', 'to')
        )->with(FundUnsubscribeResource::load());

        return FundUnsubscribeResource::collection(
            $query->paginate($request->input('per_page', 10))
        );
    }

    /**
     * Store fund unsubscribe request.
     *
     * @param StoreFundUnsubscribeRequest $request
     * @param Organization $organization
     * @return FundUnsubscribeResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StoreFundUnsubscribeRequest $request,
        Organization $organization,
    ): FundUnsubscribeResource {
        $this->authorize('show', $organization);
        $this->authorize('storeProvider', [FundProvider::class, $organization]);

        $fundUnsubscribe = FundUnsubscribe::query()->create(
            $request->only('note', 'unsubscribe_date', 'fund_provider_id')
        );

        return FundUnsubscribeResource::create($fundUnsubscribe);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateFundUnsubscribeRequest $request
     * @param Organization $organization
     * @param FundUnsubscribe $fundUnsubscribe
     * @return FundUnsubscribeResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        UpdateFundUnsubscribeRequest $request,
        Organization $organization,
        FundUnsubscribe $fundUnsubscribe
    ): FundUnsubscribeResource {
        $this->authorize('show', $organization);
        $this->authorize('updateProvider', [$fundUnsubscribe->fund_provider, $organization]);

        $fundUnsubscribe->update($request->only('state'));

        return new FundUnsubscribeResource($fundUnsubscribe);
    }
}
