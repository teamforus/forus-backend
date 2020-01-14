<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Provider;

use App\Exports\VoucherTransactionsProviderExport;
use App\Http\Requests\Api\Platform\Organizations\Transactions\IndexTransactionsRequest;
use App\Http\Resources\Provider\ProviderVoucherTransactionResource;
use App\Models\Organization;
use App\Models\VoucherTransaction;
use App\Http\Controllers\Controller;

class TransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexTransactionsRequest $request
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexTransactionsRequest $request,
        Organization $organization
    ) {
        $this->authorize('show', $organization);
        $this->authorize('indexProvider', [VoucherTransaction::class, $organization]);

        return ProviderVoucherTransactionResource::collection(
            VoucherTransaction::searchProvider($request, $organization)->with(
                ProviderVoucherTransactionResource::$load
            )->paginate(
                $request->input('per_page', 25)
            )
        );
    }

    /**
     * @param IndexTransactionsRequest $request
     * @param Organization $organization
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function export(
        IndexTransactionsRequest $request,
        Organization $organization
    ) {
        $this->authorize('index', Organization::class);
        $this->authorize('indexProvider', [VoucherTransaction::class, $organization]);

        return resolve('excel')->download(
            new VoucherTransactionsProviderExport($request, $organization),
            date('Y-m-d H:i:s') . '.xls'
        );
    }

    /**
     * @param Organization $organization
     * @param VoucherTransaction $voucherTransaction
     * @return ProviderVoucherTransactionResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        VoucherTransaction $voucherTransaction
    ) {
        $this->authorize('show', $organization);
        $this->authorize('showProvider', [$voucherTransaction, $organization]);

        return new ProviderVoucherTransactionResource($voucherTransaction->load(
            ProviderVoucherTransactionResource::$load
        ));
    }
}
