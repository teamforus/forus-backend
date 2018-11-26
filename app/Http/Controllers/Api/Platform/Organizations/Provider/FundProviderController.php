<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Provider;

use App\Http\Requests\Api\Platform\Organizations\Provider\StoreFundProviderRequest;
use App\Http\Requests\Api\Platform\Organizations\Provider\UpdateFundProviderRequest;
use App\Http\Resources\FundResource;
use App\Http\Resources\FundProviderResource;
use App\Models\Fund;
use App\Models\FundProductCategory;
use App\Models\Organization;
use App\Http\Controllers\Controller;
use App\Models\FundProvider;
use Illuminate\Http\Request;

class FundProviderController extends Controller
{
    /**
     * Display list funds available for apply as provider
     *
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function availableFunds(
        Organization $organization
    ) {
        $this->authorize('show', $organization);
        $this->authorize('indexProvider', [FundProvider::class, $organization]);

        $requestedFundsIds = $organization->organization_funds()->pluck(
            'fund_id'
        )->toArray();

        $fundIds = FundProductCategory::getModel()->whereIn(
            'product_category_id',
            $organization->product_categories->pluck('id')->toArray()
        )->whereNotIn(
            'fund_id', $requestedFundsIds
        )->select(
            'fund_id'
        )->distinct()->get()->pluck(
            'fund_id'
        )->unique()->toArray();

        $funds = Fund::getModel()->where(
            'state', '!=', 'closed'
        )->whereIn('id', $fundIds)->get();


        return FundResource::collection($funds);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        Request $request,
        Organization $organization
    ) {
        $this->authorize('show', $organization);
        $this->authorize('indexProvider', [FundProvider::class, $organization]);

        $state = $request->input('state', false);
        $organization_funds = $organization->organization_funds();

        if ($state) {
            $organization_funds->where('state', $state);
        }

        return FundProviderResource::collection(
            $organization_funds->get()
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreFundProviderRequest $request
     * @param Organization $organization
     * @return FundProviderResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StoreFundProviderRequest $request,
        Organization $organization
    ) {
        $this->authorize('show', $organization);
        $this->authorize('storeProvider', [FundProvider::class, $organization]);

        /** @var FundProvider $fundProvider */
        $fundProvider = $organization->organization_funds()->firstOrCreate($request->only([
            'fund_id'
        ]));

        resolve('forus.services.mail_notification')->providerApplied(
            $fundProvider->fund->organization->identity_address,
            $fundProvider->organization->name,
            $fundProvider->fund->organization->name,
            $fundProvider->fund->name,
            config('forus.front_ends.panel-sponsor')
        );

        return new FundProviderResource($fundProvider);
    }

    /**
     * Display the specified resource
     *
     * @param Organization $organization
     * @param FundProvider $organizationFund
     * @return FundProviderResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        FundProvider $organizationFund
    ) {
        $this->authorize('show', $organization);
        $this->authorize('showProvider', [$organizationFund, $organization]);

        return new FundProviderResource($organizationFund);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateFundProviderRequest $request
     * @param Organization $organization
     * @param FundProvider $organizationFund
     * @return FundProviderResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        UpdateFundProviderRequest $request,
        Organization $organization,
        FundProvider $organizationFund
    ) {
        $this->authorize('show', $organization);
        $this->authorize('updateProvider', [$organizationFund, $organization]);

        $organizationFund->update($request->only([
            'state'
        ]));

        return new FundProviderResource($organizationFund);
    }
}
