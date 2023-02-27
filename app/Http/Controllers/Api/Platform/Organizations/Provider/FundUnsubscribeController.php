<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Provider\FundUnsubscribes\IndexFundUnsubscribeRequest;
use App\Http\Requests\Api\Platform\Organizations\Provider\FundUnsubscribes\StoreFundUnsubscribeRequest;
use App\Http\Requests\Api\Platform\Organizations\Provider\FundUnsubscribes\UpdateFundUnsubscribeRequest;
use App\Http\Resources\FundProviderUnsubscribeResource;
use App\Models\FundProviderUnsubscribe;
use App\Models\Organization;
use App\Searches\FundUnsubscribeSearch;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
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
        $this->authorize('viewAnyProvider', [FundProviderUnsubscribe::class, $organization]);

        $search = new FundUnsubscribeSearch($request->only([
            'q', 'state', 'fund_id', 'from', 'to',
        ]), FundProviderUnsubscribe::whereHas('fund_provider', fn (Builder $q) => $q->where([
            'organization_id' => $organization->id,
        ]))->latest());

        return FundProviderUnsubscribeResource::queryCollection($search->query(), $request);
    }

    /**
     * Store fund unsubscribe request.
     *
     * @param StoreFundUnsubscribeRequest $request
     * @param Organization $organization
     * @return FundProviderUnsubscribeResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StoreFundUnsubscribeRequest $request,
        Organization $organization,
    ): FundProviderUnsubscribeResource {
        $this->authorize('show', $organization);
        $this->authorize('store', [FundProviderUnsubscribe::class, $organization]);

        $fundProviderUnsubscribe = FundProviderUnsubscribe::create(array_merge([
            'unsubscribe_at' => Carbon::parse($request->input('unsubscribe_at'))->endOfDay(),
        ], $request->only([
            'note', 'fund_provider_id',
        ])));

        return FundProviderUnsubscribeResource::create($fundProviderUnsubscribe);
    }

    /**
     * View fund unsubscribe request.
     *
     * @param Organization $organization
     * @param FundProviderUnsubscribe $fundUnsubscribe
     * @return FundProviderUnsubscribeResource
     * @throws AuthorizationException
     */
    public function view(
        Organization $organization,
        FundProviderUnsubscribe $fundUnsubscribe
    ): FundProviderUnsubscribeResource {
        $this->authorize('show', $organization);
        $this->authorize('show', [$fundUnsubscribe, $organization]);

        return FundProviderUnsubscribeResource::create($fundUnsubscribe);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateFundUnsubscribeRequest $request
     * @param Organization $organization
     * @param FundProviderUnsubscribe $fundUnsubscribe
     * @return FundProviderUnsubscribeResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        UpdateFundUnsubscribeRequest $request,
        Organization $organization,
        FundProviderUnsubscribe $fundUnsubscribe
    ): FundProviderUnsubscribeResource {
        $this->authorize('show', $organization);
        $this->authorize('cancel', [$fundUnsubscribe, $organization]);

        $fundUnsubscribe->update($request->only('canceled'));

        return FundProviderUnsubscribeResource::create($fundUnsubscribe);
    }
}
