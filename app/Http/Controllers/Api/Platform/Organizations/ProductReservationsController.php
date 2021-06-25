<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\ProductReservations\IndexProductReservationsRequest;
use App\Http\Requests\Api\Platform\Organizations\ProductReservations\StoreProductReservationRequest;
use App\Http\Requests\Api\Platform\Organizations\ProductReservations\AcceptProductReservationRequest;
use App\Http\Requests\Api\Platform\Organizations\ProductReservations\RejectProductReservationRequest;
use App\Http\Resources\ProductReservationResource;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Voucher;
use App\Scopes\Builders\ProductReservationQuery;
use App\Searches\ProductReservationsSearch;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Class ProductReservationsController
 * @package App\Http\Controllers\Api\Platform\Organizations
 */
class ProductReservationsController extends Controller
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
        $this->authorize('viewAnyProvider', [ProductReservation::class, $organization]);

        $search = new ProductReservationsSearch($request->only([
            'q', 'state', 'from', 'to', 'organization_id', 'product_id', 'fund_id',
        ]), ProductReservationQuery::whereProviderFilter(
            ProductReservation::query(),
            $organization->id
        ));

        return ProductReservationResource::collection($search->query()->with(
            ProductReservationResource::load()
        )->orderByDesc('product_reservations.created_at')->paginate(
            $request->input('per_page')
        ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreProductReservationRequest $request
     * @param Organization $organization
     * @return ProductReservationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function store(
        StoreProductReservationRequest $request,
        Organization $organization
    ): ProductReservationResource {
        $this->authorize('createProvider', [ProductReservation::class, $organization]);

        $product = Product::find($request->input('product_id'));
        $voucher = Voucher::findByAddress($request->input('voucher_address'));

        $reservation = $voucher->reserveProduct($product, $request->input('note'));

        if ($reservation->product->autoAcceptsReservations($voucher->fund)) {
            $reservation->acceptProvider();
        }

        return new ProductReservationResource($reservation->load(
            ProductReservationResource::load()
        ));
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param \App\Models\ProductReservation $productReservation
     * @return ProductReservationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        ProductReservation $productReservation
    ): ProductReservationResource {
        $this->authorize('show', $organization);
        $this->authorize('viewProvider', [$productReservation, $organization]);

        return new ProductReservationResource($productReservation);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param AcceptProductReservationRequest $request
     * @param Organization $organization
     * @param \App\Models\ProductReservation $productReservation
     * @return ProductReservationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function accept(
        AcceptProductReservationRequest $request,
        Organization $organization,
        ProductReservation $productReservation
    ): ProductReservationResource {
        $this->authorize('show', $organization);
        $this->authorize('acceptProvider', [$productReservation, $organization]);

        $productReservation->acceptProvider($organization->findEmployee($request->auth_address()));

        return new ProductReservationResource($productReservation);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param RejectProductReservationRequest $request
     * @param Organization $organization
     * @param \App\Models\ProductReservation $productReservation
     * @return ProductReservationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function reject(
        RejectProductReservationRequest $request,
        Organization $organization,
        ProductReservation $productReservation
    ): ProductReservationResource {
        $this->authorize('show', $organization);
        $this->authorize('rejectProvider', [$productReservation, $organization]);

        $productReservation->rejectOrCancelProvider($organization->findEmployee($request->auth_address()));

        return new ProductReservationResource($productReservation);
    }
}
