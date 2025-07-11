<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Vouchers\ProductReservations\IndexProductReservationRequest;
use App\Http\Resources\Sponsor\SponsorProductReservationResource;
use App\Models\Organization;
use App\Models\ProductReservation;
use App\Scopes\Builders\ProductReservationQuery;
use App\Searches\ProductReservationsSearch;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductReservationsController extends Controller
{
    /**
     * @param IndexProductReservationRequest $request
     * @param Organization $organization
     * @return AnonymousResourceCollection
     */
    public function index(
        IndexProductReservationRequest $request,
        Organization $organization,
    ): AnonymousResourceCollection {
        $this->authorize('viewAnySponsor', [ProductReservation::class, $organization]);

        $search = new ProductReservationsSearch($request->only([
            'q', 'order_by', 'order_dir', 'voucher_id',
        ]), ProductReservationQuery::whereSponsorFilter(ProductReservation::query(), $organization->id));

        return SponsorProductReservationResource::queryCollection($search->query(), $request);
    }
}
