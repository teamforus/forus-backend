<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor;

use App\Events\VoucherTransactions\VoucherTransactionCreated;
use App\Exports\VoucherTransactionsSponsorExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Transactions\IndexTransactionsRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Transactions\StoreTransactionRequest;
use App\Http\Resources\Arr\ExportFieldArrResource;
use App\Http\Resources\Sponsor\SponsorVoucherTransactionResource;
use App\Models\Organization;
use App\Models\Reimbursement;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\VoucherTransactionQuery;
use App\Statistics\Funds\FinancialStatisticQueries;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
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

        $options = array_merge($request->only([
            'fund_ids', 'postcodes', 'provider_ids', 'product_category_ids', 'targets', 'initiator',
        ]), [
            'date_to' => $request->input('to') ? Carbon::parse($request->input('to')) : null,
            'date_from' => $request->input('from') ? Carbon::parse($request->input('from')) : null,
        ]);

        if ($request->has('voucher_id') && !$organization->show_provider_transactions) {
            $options['target'] = [VoucherTransaction::TARGET_TOP_UP, VoucherTransaction::TARGET_IBAN];
            $options['initiator'] = VoucherTransaction::INITIATOR_SPONSOR;
        }

        $query = VoucherTransaction::searchSponsor($request, $organization);
        $query = (new FinancialStatisticQueries())->getFilterTransactionsQuery($organization, $options, $query);

        $total_amount = currency_format((clone $query)->sum('amount'));
        $meta = compact('total_amount');

        return SponsorVoucherTransactionResource::queryCollection(VoucherTransactionQuery::order(
            $query,
            $request->input('order_by'),
            $request->input('order_dir')
        ))->additional(compact('meta'));
    }

    /**
     * Display the specified resource.
     *
     * @param StoreTransactionRequest $request
     * @param Organization $organization
     * @return SponsorVoucherTransactionResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function store(
        StoreTransactionRequest $request,
        Organization $organization
    ): SponsorVoucherTransactionResource {
        $note = $request->input('note');
        $target = $request->input('target');
        $ibanSource = $request->input('iban_source');
        $targetTopUp = $target == VoucherTransaction::TARGET_TOP_UP;
        $targetProvider = $target == VoucherTransaction::TARGET_PROVIDER;
        $targetIban = $target == VoucherTransaction::TARGET_IBAN;
        $eventData = [];

        $voucher = Voucher::find($request->input('voucher_id'));
        $provider = Organization::find($request->input('organization_id')) ?: false;

        $this->authorize('show', $organization);
        $this->authorize('useAsSponsor', [$voucher, $targetProvider ? $provider : null]);

        $fields = match($target) {
            VoucherTransaction::TARGET_IBAN => $request->only('target_iban', 'target_name'),
            VoucherTransaction::TARGET_PROVIDER => $request->only('organization_id'),
            default => [],
        };

        if ($targetIban && $ibanSource === VoucherTransaction::TARGET_IBAN_SOURCE_REIMBURSEMENT) {
            $reimbursement = Reimbursement::find($request->get('reimbursement_id'));
            $fields = array_merge($fields, [
                'target_iban' => $reimbursement->iban,
                'target_name' => $reimbursement->iban_name,
            ]);

            $eventData['reimbursement_id'] = $reimbursement->id;
        }

        $transaction = $voucher->makeTransaction(array_merge([
            'amount' => $request->input('amount'),
            'initiator' => VoucherTransaction::INITIATOR_SPONSOR,
            'employee_id' => $request->employee($organization)->id,
            'target' => $target,
            'state' => $targetTopUp ? VoucherTransaction::STATE_SUCCESS : VoucherTransaction::STATE_PENDING,
            'payment_time' => $targetTopUp ? now() : null,
        ], $fields));

        if ($note) {
            $transaction->addNote('sponsor', $note);
            $eventData['voucher_transaction_note'] = $note;
        }

        VoucherTransactionCreated::dispatch($transaction, $eventData);

        return SponsorVoucherTransactionResource::create($transaction);
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
        $this->authorize('viewAnySponsor', [VoucherTransaction::class, $organization]);

        return ExportFieldArrResource::collection(VoucherTransactionsSponsorExport::getExportFields());
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

        $fields = $request->input('fields', VoucherTransactionsSponsorExport::getExportFields());
        $fileData = new VoucherTransactionsSponsorExport($request, $organization, $fields);
        $fileName = date('Y-m-d H:i:s') . '.' . $request->input('data_format', 'xls');

        return resolve('excel')->download($fileData, $fileName);
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

        return SponsorVoucherTransactionResource::create($voucherTransaction);
    }
}
