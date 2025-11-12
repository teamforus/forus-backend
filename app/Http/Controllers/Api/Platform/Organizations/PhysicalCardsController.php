<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\PhysicalCards\IndexPhysicalCardsRequest;
use App\Http\Resources\Sponsor\SponsorPhysicalCardResource;
use App\Models\Organization;
use App\Models\PhysicalCard;
use App\Searches\PhysicalCardSearch;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PhysicalCardsController extends Controller
{
    /**
     *  Display a listing of the resource.
     *
     * @param IndexPhysicalCardsRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(
        IndexPhysicalCardsRequest $request,
        Organization $organization,
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', [PhysicalCard::class, $organization]);

        $query = (new PhysicalCardSearch(
            $request->only(['q', 'order_by', 'order_dir', 'fund_id', 'physical_card_type_id']),
            PhysicalCard::whereRelation('voucher.fund', 'organization_id', $organization->id),
        ))->query();

        return SponsorPhysicalCardResource::queryCollection($query, $request);
    }
}
