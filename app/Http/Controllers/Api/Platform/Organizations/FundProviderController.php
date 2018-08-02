<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Resources\FundProviderResource;
use App\Models\FundProvider;
use App\Models\Organization;
use App\Http\Controllers\Controller;

class FundProviderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(
        Organization $organization
    ) {
        return FundProviderResource::collection(
            FundProvider::getModel()->whereIn(
                'fund_id',
                $organization->funds()->pluck('id')
            )->get()
        );
    }
}
