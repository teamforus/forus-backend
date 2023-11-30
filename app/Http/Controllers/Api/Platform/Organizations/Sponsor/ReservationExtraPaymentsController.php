<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\ExtraPayments\IndexExtraPaymentsRequest;
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
     * @param IndexExtraPaymentsRequest $request
     * @param Organization $organization
     * @return AnonymousResourceCollection
     */
    public function index(
        IndexExtraPaymentsRequest $request,
        Organization $organization,
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAnySponsor', [ReservationExtraPayment::class, $organization]);

        $query = ReservationExtraPayment::query()
            ->whereNotNull('paid_at')
            ->where('state', ReservationExtraPayment::EVENT_PAID);

        $search = new ReservationExtraPaymentsSearch($request->only([
            'q', 'fund_id', 'order_by', 'order_dir',
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
        ReservationExtraPayment $payment,
    ): ReservationExtraPaymentResource {
        $this->authorize('show', $organization);
        $this->authorize('viewSponsor', [$payment, $organization]);

        return ReservationExtraPaymentResource::create($payment);
    }
}
