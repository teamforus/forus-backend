<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\ProductReservations\IndexProductReservationsRequest;
use App\Http\Resources\Sponsor\ReservationExtraPaymentResource;
use App\Models\Organization;
use App\Models\ReservationExtraPayment;
use App\Scopes\Builders\ReservationExtraPaymentQuery;
use App\Searches\ReservationExtraPaymentsSearch;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReservationExtraPaymentsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexProductReservationsRequest $request
     * @param Organization $organization
     * @return AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexProductReservationsRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAnySponsor', [ReservationExtraPayment::class, $organization]);

        $query = ReservationExtraPayment::query()->whereNotNull('paid_at')
            ->where('refunded', false);

        $search = new ReservationExtraPaymentsSearch($request->only([
            'q', 'from', 'to', 'organization_id', 'product_id', 'fund_id', 'order_by', 'order_dir',
        ]), ReservationExtraPaymentQuery::whereSponsorFilter($query, $organization->id));

        return ReservationExtraPaymentResource::queryCollection($search->query());
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param ReservationExtraPayment $payment
     * @return ReservationExtraPaymentResource
     */
    public function show(
        Organization $organization,
        ReservationExtraPayment $payment
    ): ReservationExtraPaymentResource {
        $this->authorize('show', $organization);
        $this->authorize('viewSponsor', [$payment, $organization]);

        return new ReservationExtraPaymentResource($payment);
    }
}
