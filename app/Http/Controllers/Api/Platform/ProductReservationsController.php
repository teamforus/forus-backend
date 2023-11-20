<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\ProductReservations\IndexProductReservationsRequest;
use App\Http\Requests\Api\Platform\ProductReservations\ValidateProductReservationAddressRequest;
use App\Http\Requests\Api\Platform\ProductReservations\ValidateProductReservationFieldsRequest;
use App\Http\Requests\Api\Platform\ProductReservations\StoreProductReservationRequest;
use App\Http\Requests\Api\Platform\ProductReservations\UpdateProductReservationsRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\ProductReservationResource;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\ReservationExtraPayment;
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
     * @return ProductReservationResource|JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function store(StoreProductReservationRequest $request): ProductReservationResource|JsonResponse
    {
        $this->authorize('create', ProductReservation::class);

        $product = Product::find($request->input('product_id'));
        $voucher = Voucher::findByAddress($request->input('voucher_address'), $request->auth_address());
        $postCode = $request->input('postal_code') ?: '';

        $withExtraPayment = $voucher->fund->isTypeBudget() && $product->price > $voucher->amount_available;

        if ($withExtraPayment) {
            $this->authorize('createExtraPayment', [ProductReservation::class, $product, $voucher]);
        }

        $reservation = $voucher->reserveProduct($product, null, array_merge($request->only([
            'first_name', 'last_name', 'user_note', 'phone', 'birth_date', 'custom_fields',
            'street', 'house_nr', 'house_nr_addition', 'city',
        ]), [
            'postal_code' => strtoupper(preg_replace("/\s+/", "", $postCode)),
            'has_extra_payment' => $withExtraPayment,
        ]));

        if ($withExtraPayment) {
            $checkout_url = $reservation->createExtraPayment(
                $request->implementation(),
                $request->get('payment_method', ReservationExtraPayment::TYPE_MOLLIE)
            );

            return new JsonResponse(compact('checkout_url'), $checkout_url ? 200 : 500);
        }

        if ($reservation->product->autoAcceptsReservations($voucher->fund)) {
            $reservation->acceptProvider();
        }

        return ProductReservationResource::create($reservation);
    }

    /**
     * Validate product reservation request
     * @param ValidateProductReservationFieldsRequest $request
     */
    public function storeValidateFields(ValidateProductReservationFieldsRequest $request): void {}

    /**
     * Validate product reservation request
     * @param ValidateProductReservationAddressRequest $request
     */
    public function storeValidateAddress(ValidateProductReservationAddressRequest $request): void {}

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
     * @return ProductReservationResource
     * @throws \Throwable
     */
    public function update(
        UpdateProductReservationsRequest $request,
        ProductReservation $productReservation
    ): ProductReservationResource {
        $this->authorize('update', $productReservation);

        if ($request->input('state') == ProductReservation::STATE_CANCELED_BY_CLIENT) {
            $productReservation->cancelByClient();
        }

        return ProductReservationResource::create($productReservation);
    }

    /**
     * @param BaseFormRequest $request
     * @param ProductReservation $reservation
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function createExtraPayment(
        BaseFormRequest $request,
        ProductReservation $reservation
    ): JsonResponse {
        $this->authorize('payExtraPayment', $reservation);

        $url = $reservation->createExtraPayment(
            $request->implementation(),
            $request->get('payment_method', ReservationExtraPayment::TYPE_MOLLIE)
        );

        return new JsonResponse(compact('url'), $url ? 200 : 500);
    }
}
