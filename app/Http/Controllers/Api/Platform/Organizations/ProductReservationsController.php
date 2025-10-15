<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Exports\ProductReservationsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\ProductReservations\AcceptProductReservationRequest;
use App\Http\Requests\Api\Platform\Organizations\ProductReservations\FetchExtraPaymentProductReservationsRequest;
use App\Http\Requests\Api\Platform\Organizations\ProductReservations\IndexProductReservationsRequest;
use App\Http\Requests\Api\Platform\Organizations\ProductReservations\RefundExtraPaymentProductReservationRequest;
use App\Http\Requests\Api\Platform\Organizations\ProductReservations\RejectProductReservationRequest;
use App\Http\Requests\Api\Platform\Organizations\ProductReservations\StoreProductReservationBatchRequest;
use App\Http\Requests\Api\Platform\Organizations\ProductReservations\StoreProductReservationRequest;
use App\Http\Requests\Api\Platform\Organizations\ProductReservations\UpdateProductReservationRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\Arr\ExportFieldArrResource;
use App\Http\Resources\ProductReservationResource;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Voucher;
use App\Scopes\Builders\ProductReservationQuery;
use App\Scopes\Builders\VoucherQuery;
use App\Searches\ProductReservationsSearch;
use App\Services\MollieService\Exceptions\MollieException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ProductReservationsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexProductReservationsRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return AnonymousResourceCollection
     */
    public function index(
        IndexProductReservationsRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAnyProvider', [ProductReservation::class, $organization]);

        $query = ProductReservation::where(function (Builder $builder) {
            $builder->where('state', '!=', ProductReservation::STATE_PENDING);
            $builder->orWhere(function (Builder $builder) {
                $builder->where('state', ProductReservation::STATE_PENDING);
                $builder->whereHas('voucher', function (Builder $builder) {
                    VoucherQuery::whereActive($builder);
                });
            });
        });

        $search = new ProductReservationsSearch($request->only([
            'q', 'state', 'from', 'to', 'organization_id', 'product_id', 'fund_id', 'archived',
            'order_by', 'order_dir',
        ]), ProductReservationQuery::whereProviderFilter($query, $organization->id));

        return ProductReservationResource::queryCollection($search->query());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreProductReservationRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws Throwable
     * @return ProductReservationResource
     */
    public function store(
        StoreProductReservationRequest $request,
        Organization $organization
    ): ProductReservationResource {
        $this->authorize('createProvider', [ProductReservation::class, $organization]);

        $product = Product::find($request->input('product_id'));
        $voucher = Voucher::findByAddressOrPhysicalCard($request->input('number'));
        $employee = $organization->findEmployee($request->auth_address());

        $reservation = $voucher->reserveProduct($product, $employee, extraData: $request->only([
            'note',
        ]));

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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws Throwable
     * @return array
     */
    public function storeBatch(
        StoreProductReservationBatchRequest $request,
        Organization $organization,
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

                $reservation = $voucher->reserveProduct($product, $employee, extraData: array_only($item, [
                    'note',
                ]));

                $createdItems[] = $reservation->acceptProvider($employee)->id;
            } else {
                $errorsItems[] = $validator->messages()->toArray();
            }
        }

        $reservations = ProductReservation::query()->whereIn('id', $createdItems)->get();
        $reserved = ProductReservationResource::collection($reservations->load(ProductReservationResource::load()));

        return [
            'reserved' => $reserved,
            'errors' => array_reduce($errorsItems, function ($array, $item) {
                return array_merge($array, $item);
            }, []),
        ];
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param \App\Models\ProductReservation $productReservation
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return ProductReservationResource
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
     * @throws \Illuminate\Auth\Access\AuthorizationException|Throwable
     * @return ProductReservationResource
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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws Throwable
     * @return ProductReservationResource
     */
    public function reject(
        RejectProductReservationRequest $request,
        Organization $organization,
        ProductReservation $productReservation
    ): ProductReservationResource {
        $this->authorize('show', $organization);
        $this->authorize('rejectProvider', [$productReservation, $organization]);

        $productReservation->rejectOrCancelProvider(
            $organization->findEmployee($request->auth_address()),
            $request->post('note'),
            $request->post('share_note_by_email', false),
        );

        return new ProductReservationResource($productReservation);
    }

    /**
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return AnonymousResourceCollection
     * @noinspection PhpUnused
     */
    public function getExportFields(
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAnyProvider', [ProductReservation::class, $organization]);

        return ExportFieldArrResource::collection(ProductReservationsExport::getExportFields());
    }

    /**
     * @param IndexProductReservationsRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @return BinaryFileResponse
     */
    public function export(
        IndexProductReservationsRequest $request,
        Organization $organization
    ): BinaryFileResponse {
        $this->authorize('show', $organization);
        $this->authorize('viewAnyProvider', [ProductReservation::class, $organization]);

        $search = new ProductReservationsSearch($request->only([
            'q', 'state', 'from', 'to', 'organization_id', 'product_id', 'fund_id', 'archived',
        ]), ProductReservationQuery::whereProviderFilter(ProductReservation::query(), $organization->id));

        $exportType = $request->input('data_format', 'xls');
        $fileName = date('Y-m-d H:i:s') . '.' . $exportType;
        $fields = $request->input('fields', ProductReservationsExport::getExportFieldsRaw());
        $exportData = new ProductReservationsExport($search->get(), $fields);

        return resolve('excel')->download($exportData, $fileName);
    }

    /**
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @param ProductReservation $productReservation
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return ProductReservationResource
     */
    public function archive(
        BaseFormRequest $request,
        Organization $organization,
        ProductReservation $productReservation
    ): ProductReservationResource {
        $this->authorize('show', $organization);
        $this->authorize('archive', [$productReservation, $organization]);

        $productReservation->archive($organization->findEmployee($request->auth_address()));

        return new ProductReservationResource($productReservation);
    }

    /**
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @param ProductReservation $productReservation
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return ProductReservationResource
     * @noinspection PhpUnused
     */
    public function unArchive(
        BaseFormRequest $request,
        Organization $organization,
        ProductReservation $productReservation
    ): ProductReservationResource {
        $this->authorize('show', $organization);
        $this->authorize('unarchive', [$productReservation, $organization]);

        $productReservation->unArchive($organization->findEmployee($request->auth_address()));

        return new ProductReservationResource($productReservation);
    }

    /**
     * @param FetchExtraPaymentProductReservationsRequest $request
     * @param Organization $organization
     * @param ProductReservation $productReservation
     * @throws Throwable
     * @return ProductReservationResource
     */
    public function fetchExtraPayment(
        FetchExtraPaymentProductReservationsRequest $request,
        Organization $organization,
        ProductReservation $productReservation
    ): ProductReservationResource {
        $this->authorize('show', $organization);
        $this->authorize('fetchExtraPayment', [$productReservation, $organization]);

        try {
            $productReservation->fetchExtraPayment($request->employee($organization));
        } catch (MollieException $e) {
            abort(503, $e->getMessage());
        }

        return new ProductReservationResource($productReservation->refresh());
    }

    /**
     * @param RefundExtraPaymentProductReservationRequest $request
     * @param Organization $organization
     * @param ProductReservation $productReservation
     * @throws Throwable
     * @return ProductReservationResource|JsonResponse
     */
    public function refundExtraPayment(
        RefundExtraPaymentProductReservationRequest $request,
        Organization $organization,
        ProductReservation $productReservation
    ): ProductReservationResource|JsonResponse {
        $this->authorize('show', $organization);
        $this->authorize('refundExtraPayment', [$productReservation, $organization]);

        try {
            $this->authorize('fetchExtraPayment', [$productReservation, $organization]);
            $productReservation->fetchExtraPayment($request->employee($organization));

            $this->authorize('refundExtraPayment', [$productReservation, $organization]);

            $productReservation->refundExtraPayment(
                $request->employee($organization),
                $request->post('note'),
                $request->post('share_note_by_email', false),
            );
        } catch (MollieException $e) {
            abort(503, $e->getMessage());
        }

        return new ProductReservationResource($productReservation->refresh());
    }

    /**
     * @param UpdateProductReservationRequest $request
     * @param Organization $organization
     * @param ProductReservation $productReservation
     * @return ProductReservationResource
     */
    public function update(
        UpdateProductReservationRequest $request,
        Organization $organization,
        ProductReservation $productReservation
    ): ProductReservationResource {
        $this->authorize('show', $organization);
        $this->authorize('update', [$productReservation, $organization]);

        $productReservation->update($request->only('invoice_number'));

        return new ProductReservationResource($productReservation);
    }
}
