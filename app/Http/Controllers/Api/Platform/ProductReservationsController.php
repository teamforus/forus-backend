<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\ProductReservations\IndexProductReservationsRequest;
use App\Http\Requests\Api\Platform\ProductReservations\StoreProductReservationRequest;
use App\Http\Requests\Api\Platform\ProductReservations\UpdateProductReservationsRequest;
use App\Http\Resources\ProductReservationResource;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Voucher;
use App\Searches\ProductReservationsSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductReservationsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexProductReservationsRequest $request
     * @return AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(IndexProductReservationsRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ProductReservation::class);

        $builder = ProductReservation::whereHas('voucher', function(Builder $builder) use ($request) {
            $builder->where('identity_address', $request->auth_address());
        });

        $search = new ProductReservationsSearch(array_merge($request->only([
            'q', 'state', 'from', 'to', 'organization_id', 'product_id', 'fund_id', 'archived',
        ]), [
            'is_webshop' => true,
        ]), $builder);

        return ProductReservationResource::queryCollection(
            $search->query()->orderByDesc('created_at'),
            $request
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreProductReservationRequest $request
     * @return ProductReservationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function store(StoreProductReservationRequest $request): ProductReservationResource
    {
        $this->authorize('create', ProductReservation::class);

        $product = Product::find($request->input('product_id'));
        $voucher = Voucher::findByAddress($request->input('voucher_address'), $request->auth_address());

        $reservation = $voucher->reserveProduct($product, null, $request->only([
            'first_name', 'last_name', 'user_note', 'phone', 'address', 'birth_date',
        ]));

        if ($reservation->product->autoAcceptsReservations($voucher->fund)) {
            $reservation->acceptProvider();
        }

        return ProductReservationResource::create($reservation);
    }

    /**
     * Validate product reservation request
     * @param StoreProductReservationRequest $request
     */
    public function storeValidate(StoreProductReservationRequest $request): void {}

    /**
     * Display the specified resource.
     *
     * @param \App\Models\ProductReservation $productReservation
     * @return ProductReservationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(ProductReservation $productReservation): ProductReservationResource
    {
        $this->authorize('view', $productReservation);

        return ProductReservationResource::create($productReservation);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateProductReservationsRequest $request
     * @param ProductReservation $productReservation
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function update(
        UpdateProductReservationsRequest $request,
        ProductReservation $productReservation
    ): JsonResponse {
        $this->authorize('update', $productReservation);

        if ($request->input('state') == ProductReservation::STATE_CANCELED_BY_CLIENT) {
            $productReservation->cancelByClient();
        }

        return new JsonResponse([]);
    }
}
