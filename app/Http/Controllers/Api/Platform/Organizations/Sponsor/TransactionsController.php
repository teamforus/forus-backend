<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor;

use App\Exports\VoucherTransactionsSponsorExport;
use App\Http\Requests\Api\Platform\Organizations\Transactions\IndexTransactionsRequest;
use App\Http\Resources\Sponsor\SponsorVoucherTransactionResource;
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
        $this->authorize('viewAnySponsor', [VoucherTransaction::class, $organization]);

        $transactionsQuery = VoucherTransaction::searchSponsor($request, $organization);

        $meta = [
            'total_amount' => currency_format($transactionsQuery->sum('amount'))
        ];

        return SponsorVoucherTransactionResource::collection(
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
    ) {
        $this->authorize('show', $organization);
        $this->authorize('viewAnySponsor', [VoucherTransaction::class, $organization]);

        return resolve('excel')->download(
            new VoucherTransactionsSponsorExport($request, $organization),
            date('Y-m-d H:i:s') . '.xls'
        );
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param VoucherTransaction $voucherTransaction
     * @return SponsorVoucherTransactionResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        VoucherTransaction $voucherTransaction
    ) {
        $this->authorize('show', $organization);
        $this->authorize('showSponsor', [$voucherTransaction, $organization]);

        return new SponsorVoucherTransactionResource($voucherTransaction);
    }
}
