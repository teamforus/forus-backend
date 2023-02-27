<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\FundUnsubscribes\IndexFundUnsubscribeRequest;
use App\Http\Resources\FundProviderUnsubscribeResource;
use App\Models\FundProviderUnsubscribe;
use App\Models\Organization;
use App\Searches\FundUnsubscribeSearch;
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
        $this->authorize('viewAnySponsor', [FundProviderUnsubscribe::class, $organization]);

        $search = new FundUnsubscribeSearch($request->only([
            'q', 'state', 'fund_id', 'from', 'to',
        ]), FundProviderUnsubscribe::whereHas('fund_provider.fund', fn (Builder $q) => $q->where([
            'organization_id' => $organization->id,
        ])));

        return FundProviderUnsubscribeResource::queryCollection($search->query(), $request);
    }
}
