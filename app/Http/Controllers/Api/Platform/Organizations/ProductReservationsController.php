<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\ProductReservations\IndexProductReservationsRequest;
use App\Http\Requests\Api\Platform\Organizations\ProductReservations\StoreProductReservationBatchRequest;
use App\Http\Requests\Api\Platform\Organizations\ProductReservations\StoreProductReservationRequest;
use App\Http\Requests\Api\Platform\Organizations\ProductReservations\AcceptProductReservationRequest;
use App\Http\Requests\Api\Platform\Organizations\ProductReservations\RejectProductReservationRequest;
use App\Http\Resources\ProductReservationResource;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Voucher;
use App\Scopes\Builders\ProductReservationQuery;
use App\Scopes\Builders\VoucherQuery;
use App\Searches\ProductReservationsSearch;
use Illuminate\Database\Eloquent\Builder;
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

        $query = ProductReservation::where(function(Builder $builder) {
            $builder->where('state', '!=', ProductReservation::STATE_PENDING);
            $builder->orWhere(function(Builder $builder) {
                $builder->where('state', ProductReservation::STATE_PENDING);
                $builder->whereHas('voucher', function(Builder $builder) {
                    VoucherQuery::whereNotExpiredAndActive($builder);
                });
            });
        });

        $search = new ProductReservationsSearch($request->only([
            'q', 'state', 'from', 'to', 'organization_id', 'product_id', 'fund_id',
        ]), ProductReservationQuery::whereProviderFilter($query, $organization->id));

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
        $voucher = Voucher::findByAddressOrPhysicalCard($request->input('number'));
        $employee = $organization->findEmployee($request->auth_address());

        $reservation = $voucher->reserveProduct($product, $employee, $request->only('note'));
        $reservation->acceptProvider();

        return new ProductReservationResource($reservation->load(
            ProductReservationResource::load()
        ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreProductReservationBatchRequest $request
     * @param Organization $organization
     * @return array
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function storeBatch(
        StoreProductReservationBatchRequest $request,
        Organization $organization
    ): array {
        $this->authorize('createProviderBatch', [ProductReservation::class, $organization]);

        $reservations = $request->input('reservations');
        $employee = $organization->findEmployee($request->auth_address());

        $index = 0;
        $createdItems = [];
        $errorsItems = [];

        while (count($reservations) > $index) {
            $slice = array_slice($reservations, $index++, 1, true);
            $item = array_slice($slice, 0, 1)[0];
            $validator = $request->validateRows($slice);

            if ($validator->passes()) {
                $product = Product::find(array_get($item, 'product_id'));
                $voucher = Voucher::findByAddressOrPhysicalCard(array_get($item, 'number'));
                $reservation = $voucher->reserveProduct($product, $employee, array_only($item, 'note'));

                $createdItems[] = $reservation->acceptProvider($employee)->id;
            } else {
                $errorsItems[] = $validator->messages()->toArray();
            }
        }

        $reservations = ProductReservation::query()->whereIn('id', $createdItems)->get();
        $reserved = ProductReservationResource::collection($reservations->load(ProductReservationResource::load()));

        return [
            'reserved' => $reserved,
            'errors' => array_reduce($errorsItems, function($array, $item) {
                return array_merge($array, $item);
            }, []),
        ];
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
     * @throws \Exception
     */
    public function reject(
        RejectProductReservationRequest $request,
        Organization $organization,
        ProductReservation $productReservation
    ): ProductReservationResource {
        $this->authorize('show', $organization);
        $this->authorize('rejectProvider', [$productReservation, $organization]);

        $productReservation->rejectOrCancelProvider($organization->findEmployee(
            $request->auth_address()
        ));

        return new ProductReservationResource($productReservation);
    }
}
