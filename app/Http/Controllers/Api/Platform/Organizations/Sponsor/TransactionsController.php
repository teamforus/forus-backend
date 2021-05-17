<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor;

use App\Exports\VoucherTransactionsSponsorExport;
use App\Http\Requests\Api\Platform\Organizations\Transactions\IndexTransactionsRequest;
use App\Http\Resources\Sponsor\SponsorVoucherTransactionResource;
use App\Models\Organization;
use App\Models\VoucherTransaction;
use App\Http\Controllers\Controller;
use App\Statistics\Funds\FinancialStatisticQueries;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Class TransactionsController
 * @package App\Http\Controllers\Api\Platform\Organizations\Sponsor
 * @noinspection PhpUnused
 */
class TransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexTransactionsRequest $request
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function index(
        IndexTransactionsRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAnySponsor', [VoucherTransaction::class, $organization]);

        $query = VoucherTransaction::searchSponsor($request, $organization);
        $total_amount = currency_format((clone $query)->sum('amount'));

        $options = $request->only(array_merge([
            'fund_ids', 'postcodes', 'provider_ids', 'product_category_ids',
        ], [
            'date_to' => $request->input('to') ? Carbon::parse($request->input('to')) : null,
            'date_from' => $request->input('from') ? Carbon::parse($request->input('from')) : null,
        ]));

        $query = (new FinancialStatisticQueries())->getFilterTransactionsQuery($organization, $options, $query);
        $meta = compact('total_amount');

        return SponsorVoucherTransactionResource::collection($query->with(
            SponsorVoucherTransactionResource::load()
        )->paginate($request->input('per_page')))->additional(compact('meta'));
    }

    /**
     * @param IndexTransactionsRequest $request
     * @param Organization $organization
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @noinspection PhpUnused
     */
    public function export(
        IndexTransactionsRequest $request,
        Organization $organization
    ): BinaryFileResponse {
        $this->authorize('show', $organization);
        $this->authorize('viewAnySponsor', [VoucherTransaction::class, $organization]);

        $type = $request->input('export_format', 'xls');

        return resolve('excel')->download(
            new VoucherTransactionsSponsorExport($request, $organization),
            date('Y-m-d H:i:s') . '.' . $type
        );
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param VoucherTransaction $voucherTransaction
     * @return SponsorVoucherTransactionResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function show(
        Organization $organization,
        VoucherTransaction $voucherTransaction
    ): SponsorVoucherTransactionResource {
        $this->authorize('show', $organization);
        $this->authorize('showSponsor', [$voucherTransaction, $organization]);

        return new SponsorVoucherTransactionResource($voucherTransaction->load(
            SponsorVoucherTransactionResource::load()
        ));
    }
}
