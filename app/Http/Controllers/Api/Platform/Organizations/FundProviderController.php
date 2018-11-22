<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Resources\FundProviderResource;
use App\Models\FundProvider;
use App\Models\Organization;
use App\Http\Controllers\Controller;

class FundProviderController extends Controller
{
    /**
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        Organization $organization
    ) {
        $this->authorize('show', $organization);
        $this->authorize('indexSponsor', [FundProvider::class, $organization]);

        return FundProviderResource::collection(
            FundProvider::getModel()->whereIn(
                'fund_id',
                $organization->funds()->pluck('id')
            )->get()
        );
    }
}
