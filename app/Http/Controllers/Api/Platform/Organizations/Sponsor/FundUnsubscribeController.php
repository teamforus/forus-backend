<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Provider\FundUnsubscribes\IndexFundUnsubscribeRequest;
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
        $this->authorize('viewAnySponsor', [FundProvider::class, $organization]);

        $query = FundUnsubscribe::searchSponsor(
            $organization, $request->only('states')
        )->with(FundUnsubscribeResource::load());

        return FundUnsubscribeResource::collection(
            $query->paginate($request->input('per_page', 10))
        );
    }
}
