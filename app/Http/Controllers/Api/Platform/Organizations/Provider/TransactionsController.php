<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Provider;

use App\Exports\VoucherTransactionsProviderExport;
use App\Exports\VoucherTransactionsSponsorExport;
use App\Http\Requests\Api\Platform\Organizations\Provider\Transactions\IndexTransactionsRequest;
use App\Http\Resources\Arr\ExportFieldArrResource;
use App\Http\Resources\Provider\ProviderVoucherTransactionResource;
use App\Models\Organization;
use App\Models\VoucherTransaction;
use App\Http\Controllers\Controller;
use App\Scopes\Builders\VoucherTransactionQuery;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexTransactionsRequest $request
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function index(
        IndexTransactionsRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAnyProvider', [VoucherTransaction::class, $organization]);

        $query = VoucherTransaction::searchProvider($request, $organization);
        
        $meta = [
            'total_amount' => currency_format((clone $query)->sum('amount')),
        ];
        
        return ProviderVoucherTransactionResource::queryCollection(VoucherTransactionQuery::order(
            $query,
            $request->input('order_by'),
            $request->input('order_dir')
        ))->additional(compact('meta'));
    }

    /**
     * @param Organization $organization
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     * @noinspection PhpUnused
     */
    public function getExportFields(
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAnyProvider', [VoucherTransaction::class, $organization]);

        return ExportFieldArrResource::collection(VoucherTransactionsProviderExport::getExportFields());
    }

    /**
     * @param IndexTransactionsRequest $request
     * @param Organization $organization
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws AuthorizationException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function export(
        IndexTransactionsRequest $request,
        Organization $organization
    ): BinaryFileResponse {
        $this->authorize('show', $organization);
        $this->authorize('viewAnyProvider', [VoucherTransaction::class, $organization]);

        $fields = $request->input('fields', VoucherTransactionsSponsorExport::getExportFields());
        $type = $request->input('data_format', 'xls');

        return resolve('excel')->download(
            new VoucherTransactionsProviderExport($request, $organization, $fields),
            date('Y-m-d H:i:s') . '.' . $type
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
    ): ProviderVoucherTransactionResource {
        $this->authorize('show', $organization);
        $this->authorize('showProvider', [$voucherTransaction, $organization]);

        return ProviderVoucherTransactionResource::create($voucherTransaction);
    }
}
