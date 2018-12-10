<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Resources\FundProviderResource;
use App\Models\FundProvider;
use App\Models\Organization;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FundProviderController extends Controller
{
    /**
     * @param Organization $organization
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(
        Organization $organization,
        Request $request
    ) {
        $this->authorize('show', $organization);
        $this->authorize('indexSponsor', [FundProvider::class, $organization]);

        return FundProviderResource::collection(
            FundProvider::getModel()->whereIn(
                'fund_id',
                $organization->funds()->pluck('id')
            )->paginate(
                $request->has('per_page') ? $request->input('per_page') : null
            )
        );
    }
}
