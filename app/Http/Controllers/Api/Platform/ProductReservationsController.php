<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\ProductReservations\IndexProductReservationsRequest;
use App\Http\Requests\Api\Platform\ProductReservations\StoreProductReservationRequest;
use App\Http\Resources\ProductReservationResource;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Voucher;
use App\Searches\ProductReservationsSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Class ProductReservationsController
 * @package App\Http\Controllers\Api\Platform
 */
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

        $search = new ProductReservationsSearch($request->only([
            'q', 'state', 'from', 'to', 'organization_id', 'product_id', 'fund_id',
        ]), $builder);

        return ProductReservationResource::collection($search->query()->with(
            ProductReservationResource::load()
        )->orderByDesc('created_at')->paginate($request->input('per_page')));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreProductReservationRequest $request
     * @return ProductReservationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function store(StoreProductReservationRequest $request): ProductReservationResource
    {
        $this->authorize('create', ProductReservation::class);

        $product = Product::find($request->input('product_id'));
        $voucher = Voucher::findByAddress($request->input('voucher_address'), $request->auth_address());

        $reservation = $voucher->reserveProduct($product);

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
     * @param \App\Models\ProductReservation $productReservation
     * @return ProductReservationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(ProductReservation $productReservation): ProductReservationResource
    {
        $this->authorize('view', $productReservation);

        return new ProductReservationResource($productReservation->load(
            ProductReservationResource::load()
        ));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\ProductReservation $productReservation
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function destroy(ProductReservation $productReservation): JsonResponse
    {
        $this->authorize('delete', $productReservation);

        $productReservation->cancelByClient();

        return response()->json([]);
    }
}
