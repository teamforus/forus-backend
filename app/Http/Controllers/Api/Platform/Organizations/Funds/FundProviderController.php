<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Funds;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Funds\FundProviders\IndexFundProviderRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\FundProviders\UpdateFundProviderRequest;
use App\Http\Resources\FundProviderResource;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Organization;
use App\Scopes\Builders\FundProviderQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Throwable;

class FundProviderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexFundProviderRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(
        IndexFundProviderRequest $request,
        Organization $organization,
        Fund $fund
    ): AnonymousResourceCollection {
        $this->authorize('viewAnySponsor', [FundProvider::class, $organization, $fund]);

        $query = $fund->providers()->getQuery();

        if ($request->has('q')) {
            $query = FundProviderQuery::queryFilter($query, $request->input('q'));
        }

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->input('organization_id'));
        }

        if ($request->has('state')) {
            $query->where('state', $request->input('state'));
        }

        return FundProviderResource::queryCollection(FundProviderQuery::sortByRevenue($query, $fund->id), $request);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Fund $fund
     * @param FundProvider $organizationFund
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return FundProviderResource
     */
    public function show(
        Organization $organization,
        Fund $fund,
        FundProvider $organizationFund
    ): FundProviderResource {
        $this->authorize('showSponsor', [$organizationFund, $organization, $fund]);

        return FundProviderResource::create($organizationFund);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateFundProviderRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @param FundProvider $fundProvider
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws Throwable
     * @return FundProviderResource
     */
    public function update(
        UpdateFundProviderRequest $request,
        Organization $organization,
        Fund $fund,
        FundProvider $fundProvider
    ): FundProviderResource {
        $this->authorize('show', $organization);
        $this->authorize('show', [$fund, $organization]);
        $this->authorize('updateSponsor', [$fundProvider, $organization, $fund]);

        DB::transaction(function () use ($organization, $fundProvider, $request, $fund) {
            $fundProvider->update($request->only([
                'allow_products', 'allow_budget', 'excluded',
                ...($organization->allow_provider_extra_payments ? [
                    'allow_extra_payments', 'allow_extra_payments_full',
                ] : []),
            ]));

            if ($request->post('state') && ($request->post('state') !== $fundProvider->state)) {
                $fundProvider->setState($request->post('state'));
            }

            if ($request->post('enable_products')) {
                $fundProvider->approveProducts($request->input('enable_products'));
            }

            if ($request->post('disable_products')) {
                $fundProvider->declineProducts($request->input('disable_products'));
            }

            $fundProvider->update([
                'allow_some_products' => $fundProvider->fund_provider_products()->count() > 0,
            ]);
        });

        return new FundProviderResource($fundProvider);
    }
}
