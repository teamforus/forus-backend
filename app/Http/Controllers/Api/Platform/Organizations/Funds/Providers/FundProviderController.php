<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Funds\Providers;

use App\Http\Requests\Api\Platform\Organizations\Provider\UpdateFundProviderRequest;
use App\Http\Resources\FundProviderResource;
use App\Models\Fund;
use App\Models\Organization;
use App\Http\Controllers\Controller;
use App\Models\FundProvider;
use Illuminate\Http\Request;

class FundProviderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(
        Request $request,
        Organization $organization
    ) {
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
     * Display the specified resource
     *
     * @param Organization $organization
     * @param Fund $fund
     * @param FundProvider $organizationFund
     * @return FundProviderResource|FundProvider
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        Fund $fund,
        FundProvider $organizationFund
    ) {
        $this->authorize('show', $organization);
        $this->authorize('show', $fund);
        $this->authorize('show', $organizationFund);

        return new FundProviderResource($organizationFund);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateFundProviderRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @param FundProvider $organizationFund
     * @return FundProviderResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        UpdateFundProviderRequest $request,
        Organization $organization,
        Fund $fund,
        FundProvider $organizationFund
    ) {
        $this->authorize('update', $organization);
        $this->authorize('update', $fund);
        $this->authorize('update', [
            $organizationFund, $request->input('state')
        ]);

        $organizationFund->update($request->only([
            'state'
        ]));

        return new FundProviderResource($organizationFund);
    }
}
