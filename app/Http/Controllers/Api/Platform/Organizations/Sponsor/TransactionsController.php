<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor;

use App\Exports\VoucherTransactionsSponsorExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Transactions\IndexTransactionsRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Transactions\StoreTransactionBatchRequest;
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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @noinspection PhpUnused
     */
    public function index(
        IndexTransactionsRequest $request,
        Organization $organization,
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAnySponsor', [VoucherTransaction::class, $organization]);

        $from = $request->input('from');
        $to = $request->input('to');

        $options = array_merge($request->only([
            'fund_ids', 'postcodes', 'provider_ids', 'product_category_ids', 'targets', 'initiator',
        ]), [
            'date_from' => $from ? Carbon::parse($from)->startOfDay() : null,
            'date_to' => $to ? Carbon::parse($to)->endOfDay() : null,
        ]);

        if (!$organization->show_provider_transactions &&
            ($request->has('voucher_id') || $request->has('reservation_voucher_id'))) {
            $options['targets'] = [
                VoucherTransaction::TARGET_IBAN,
                VoucherTransaction::TARGET_PAYOUT,
                VoucherTransaction::TARGET_TOP_UP,
            ];

            $options['initiator'] = [
                VoucherTransaction::INITIATOR_SPONSOR,
                VoucherTransaction::INITIATOR_REQUESTER,
            ];
        }

        $query = VoucherTransaction::searchSponsor($request, $organization);
        $query = (new FinancialStatisticQueries())->getFilterTransactionsQuery($organization, $options, $query);

        $total_amount = currency_format((clone $query)->sum('amount'));
        $total_amount_locale = currency_format_locale($total_amount);
        $meta = compact('total_amount', 'total_amount_locale');

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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return SponsorVoucherTransactionResource
     * @noinspection PhpUnused
     */
    public function store(
        StoreTransactionRequest $request,
        Organization $organization
    ): SponsorVoucherTransactionResource {
        $target = $request->input('target');
        $voucher = Voucher::find($request->input('voucher_id'));
        $employee = $request->employee($organization);

        $provider = Organization::find($request->input('organization_id')) ?: false;
        $provider = $target == VoucherTransaction::TARGET_PROVIDER ? $provider : null;

        $reimbursement = $request->input('target_reimbursement_id');
        $reimbursement = $reimbursement ? Reimbursement::find($reimbursement) : null;

        $this->authorize('show', $organization);
        $this->authorize('useAsSponsor', [$voucher, $provider]);

        $reimbursementFields = $reimbursement ? [
            'target_iban' => $reimbursement->iban,
            'target_name' => $reimbursement->iban_name,
            'target_reimbursement_id' => $reimbursement->id,
        ] : [];

        return SponsorVoucherTransactionResource::create(match ($target) {
            VoucherTransaction::TARGET_PROVIDER => $voucher->makeTransactionBySponsor(
                $employee,
                $request->only('target', 'amount', 'organization_id'),
                $request->input('note'),
                $request->boolean('note_shared')
            ),
            VoucherTransaction::TARGET_IBAN => $voucher->makeTransactionBySponsor(
                $employee,
                [
                    ...$request->only('target', 'amount', 'target_iban', 'target_name'),
                    ...$reimbursementFields,
                ],
                $request->input('note'),
            ),
            VoucherTransaction::TARGET_TOP_UP => $voucher->makeSponsorTopUpTransaction(
                $employee,
                $request->input('amount'),
                $request->input('note'),
            ),
        });
    }

    /**
     * @param StoreTransactionBatchRequest $request
     * @param Organization $organization
     * @throws AuthorizationException
     * @return JsonResponse
     */
    public function storeBatchValidate(
        StoreTransactionBatchRequest $request,
        Organization $organization
    ) {
        $this->authorize('storeBatchAsSponsor', [VoucherTransaction::class, $organization]);

        return new JsonResponse([], $request->isAuthenticated() ? 200 : 403);
    }

    /**
     * @param StoreTransactionBatchRequest $request
     * @param Organization $organization
     * @throws AuthorizationException
     * @return JsonResponse
     */
    public function storeBatch(
        StoreTransactionBatchRequest $request,
        Organization $organization,
    ) {
        $this->authorize('storeBatchAsSponsor', [VoucherTransaction::class, $organization]);

        $file = $request->post('file');
        $employee = $request->employee($organization);
        $transactions = $request->input('transactions');

        $index = 0;
        $createdItems = [];
        $errorsItems = [];

        $event = $employee->logCsvUpload($employee::EVENT_UPLOADED_TRANSACTIONS, $file, $transactions);

        while (count($transactions) > $index) {
            $slice = array_slice($transactions, $index++, 1, true);
            $item = array_first($slice);
            $validator = $request->validateRows($slice);

            if ($validator->passes()) {
                $voucher = Voucher::firstWhere('number', Arr::get($item, 'voucher_number'));

                $createdItems[] = $voucher->makeTransactionBySponsor($employee, [
                    'target_iban' => $item['direct_payment_iban'],
                    'target_name' => $item['direct_payment_name'],
                    'target' => VoucherTransaction::TARGET_IBAN,
                    ...Arr::only($item, ['amount', 'uid']),
                ], Arr::get($item, 'note'))->id;
            } else {
                $errorsItems[] = $validator->messages()->toArray();
            }
        }

        $query = VoucherTransaction::query()->whereIn('id', $createdItems);

        $event->forceFill([
            'data->uploaded_file_meta->state' => 'success',
            'data->uploaded_file_meta->created_ids' => (clone $query)->pluck('id')->toArray(),
        ])->update();

        return new JsonResponse([
            'created' => SponsorVoucherTransactionResource::queryCollection($query, (clone $query)->count()),
            'errors' => array_reduce($errorsItems, fn ($array, $item) => array_merge($array, $item), []),
        ]);
    }

    /**
     * @param Organization $organization
     * @throws AuthorizationException
     * @return AnonymousResourceCollection
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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @noinspection PhpUnused
     */
    public function export(
        IndexTransactionsRequest $request,
        Organization $organization
    ): BinaryFileResponse {
        $this->authorize('show', $organization);
        $this->authorize('viewAnySponsor', [VoucherTransaction::class, $organization]);

        $fields = $request->input('fields', VoucherTransactionsSponsorExport::getExportFieldsRaw());
        $fileData = new VoucherTransactionsSponsorExport($request, $organization, $fields);
        $fileName = date('Y-m-d H:i:s') . '.' . $request->input('data_format', 'xls');

        return resolve('excel')->download($fileData, $fileName);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param VoucherTransaction $voucherTransaction
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return SponsorVoucherTransactionResource
     * @noinspection PhpUnused
     */
    public function show(
        Organization $organization,
        VoucherTransaction $voucherTransaction,
    ): SponsorVoucherTransactionResource {
        $this->authorize('show', $organization);
        $this->authorize('showSponsor', [$voucherTransaction, $organization]);

        return SponsorVoucherTransactionResource::create($voucherTransaction);
    }
}
