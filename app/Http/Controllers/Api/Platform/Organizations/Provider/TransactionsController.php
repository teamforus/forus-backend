<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Provider;

use App\Exports\VoucherTransactionsProviderExport;
use App\Http\Requests\Api\Platform\Organizations\Transactions\IndexTransactionsRequest;
use App\Http\Resources\Provider\ProviderVoucherTransactionResource;
use App\Models\Organization;
use App\Models\VoucherTransaction;
use App\Http\Controllers\Controller;
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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexTransactionsRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAnyProvider', [VoucherTransaction::class, $organization]);

        $transactionsQuery = VoucherTransaction::searchProvider($request, $organization)->with(
            ProviderVoucherTransactionResource::$load
        );
        
        $meta = [
            'total_amount' => currency_format($transactionsQuery->sum('amount'))
        ];
        
        return ProviderVoucherTransactionResource::collection(
            $transactionsQuery->paginate($request->input('per_page', 25))
        )->additional(compact('meta'));
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
    ): BinaryFileResponse {
        $this->authorize('show', $organization);
        $this->authorize('viewAnyProvider', [VoucherTransaction::class, $organization]);

        $type = $request->input('export_format', 'xls');

        return resolve('excel')->download(
            new VoucherTransactionsProviderExport($request, $organization),
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

        return new ProviderVoucherTransactionResource($voucherTransaction->load(
            ProviderVoucherTransactionResource::$load
        ));
    }
}
