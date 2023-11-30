<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\ProductReservations\IndexProductReservationsRequest;
use App\Http\Requests\Api\Platform\ProductReservations\ValidateProductReservationAddressRequest;
use App\Http\Requests\Api\Platform\ProductReservations\ValidateProductReservationFieldsRequest;
use App\Http\Requests\Api\Platform\ProductReservations\StoreProductReservationRequest;
use App\Http\Resources\ProductReservationResource;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Voucher;
use App\Searches\ProductReservationsSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Throwable;

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
        DB::beginTransaction();

        try {
            $product = Product::find($request->input('product_id'));
            $postCode = $request->input('postal_code') ?: '';

            $voucher = Voucher::findByAddress(
                $request->input('voucher_address'),
                $request->auth_address(),
            );

            $extraPaymentRequired =
                $voucher->fund->isTypeBudget() &&
                $product->price > $voucher->amount_available;

            if ($extraPaymentRequired) {
                $this->authorize('createExtraPayment', [ProductReservation::class, $product, $voucher]);
            }

            $reservation = $voucher->reserveProduct($product, null, [
                ...$request->only(array_merge([
                    'first_name', 'last_name', 'user_note',
                ], $product->reservation_fields ? [
                    'phone', 'birth_date', 'custom_fields', 'street', 'house_nr', 'house_nr_addition', 'city',
                ] : [])),
                ...$product->reservation_fields ? [
                    'postal_code' => strtoupper(preg_replace("/\s+/", "", $postCode))
                ] : [],
                'has_extra_payment' => $extraPaymentRequired,
            ]);

            if ($extraPaymentRequired) {
                $payment = $reservation->createExtraPayment($request->implementation());

                if ($payment) {
                    DB::commit();

                    return new JsonResponse([
                        'checkout_url' => $payment->getCheckoutUrl(),
                    ], 200);
                }

                DB::rollBack();

                return new JsonResponse([
                    'message' => "Could prepare the extra payment.",
                ], 200);
            }

            if ($reservation->product->autoAcceptsReservations($voucher->fund)) {
                $reservation->acceptProvider();
            }

            DB::commit();
            return ProductReservationResource::create($reservation);
        } catch (Throwable) {
            DB::rollBack();
        }

        return new JsonResponse([
            'message' => "Something went wrong, please try again later.",
        ], 200);
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
     * @param ProductReservation $reservation
     * @return ProductReservationResource
     * @throws Throwable
     */
    public function cancel(
        ProductReservation $reservation,
    ): ProductReservationResource {
        $this->authorize('cancelAsRequester', $reservation);

        $reservation->cancelByClient();

        return ProductReservationResource::create($reservation);
    }

    /**
     * @param ProductReservation $reservation
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function checkoutExtraPayment(
        ProductReservation $reservation,
    ): JsonResponse {
        $this->authorize('checkoutExtraPayment', $reservation);

        $payment = $reservation->extra_payment?->getPayment();

        if (!$payment) {
            abort(503, "Extra payment not found!");
        }

        if ($payment->isPaid()) {
            abort(503, "Extra payment already paid!");
        }

        if (!$payment->isOpen()) {
            abort(503, "Extra payment not open for payment!");
        }

        return new JsonResponse([
            'url' => $payment->getCheckoutUrl(),
        ]);
    }
}
