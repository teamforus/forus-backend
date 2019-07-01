<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Funds;

use App\Http\Resources\BunqMeIdealResource;
use App\Models\BunqMeTab;
use App\Http\Controllers\Controller;
use App\Models\Fund;
use App\Models\Organization;
use Illuminate\Http\Request;

class BunqMeTabsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param Organization $organization
     * @param Fund $fund
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        Request $request,
        Organization $organization,
        Fund $fund
    ) {
        $this->authorize('indexPublic', [
            BunqMeTab::class, $fund, $organization
        ]);

        return BunqMeIdealResource::collection(
            BunqMeTab::query()->where([
                'status' => BunqMeTab::STATUS_PAID
            ])->paginate($request->input('per_page', 20))
        );
    }

    /**
     * Display a listing of the resource.
     *
     * @param Organization $organization
     * @param Fund $fund
     * @param BunqMeTab $bunqMeTab
     * @return BunqMeIdealResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        Fund $fund,
        BunqMeTab $bunqMeTab
    ) {
        $this->authorize('showPublic', [
            $bunqMeTab, $fund, $organization
        ]);

        return new BunqMeIdealResource($bunqMeTab);
    }
}
