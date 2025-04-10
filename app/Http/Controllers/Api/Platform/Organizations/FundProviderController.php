<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Provider\IndexFundProviderRequest;
use App\Http\Resources\FundProviderResource;
use App\Models\FundProvider;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FundProviderController extends Controller
{
    /**
     * Show organization providers.
     *
     * @param IndexFundProviderRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(
        IndexFundProviderRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAnySponsor', [FundProvider::class, $organization]);

        return FundProviderResource::queryCollection(FundProvider::search($request, $organization), $request);
    }
}
