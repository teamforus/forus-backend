<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor;

use App\Exports\VoucherTransactionsSponsorExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Transactions\IndexTransactionsRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Transactions\StorePayoutTransactionBatchRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Transactions\StorePayoutTransactionRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Transactions\StoreTransactionBatchRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Transactions\StoreTransactionRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Transactions\UpdatePayoutTransactionRequest;
use App\Http\Resources\Arr\ExportFieldArrResource;
use App\Http\Resources\Sponsor\SponsorVoucherTransactionResource;
use App\Models\Data\BankAccount;
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
use Random\RandomException;
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
            $options['target'] = [
                VoucherTransaction::TARGET_IBAN,
                VoucherTransaction::TARGET_PAYOUT,
                VoucherTransaction::TARGET_TOP_UP,
            ];
            $options['initiator'] = VoucherTransaction::INITIATOR_SPONSOR;
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
     * @return SponsorVoucherTransactionResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
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
            VoucherTransaction::TARGET_TOP_UP => $voucher->makeTransactionBySponsor(
                $employee,
                $request->only('target', 'amount'),
                $request->input('note'),
            ),
        });
    }

    /**
     * Display the specified resource.
     *
     * @param StorePayoutTransactionRequest $request
     * @param Organization $organization
     * @return SponsorVoucherTransactionResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function storePayout(
        StorePayoutTransactionRequest $request,
        Organization $organization,
    ): SponsorVoucherTransactionResource {
        $this->authorize('show', $organization);
        $this->authorize('storePayoutsSponsor', [VoucherTransaction::class, $organization]);

        $fund = $organization->funds()->find($request->input('fund_id'));
        $employee = $request->employee($organization);

        $amount = $request->input('amount_preset_id') ?
            $fund->amount_presets?->find($request->input('amount_preset_id')) :
            $request->input('amount');

        $bankAccount = new BankAccount(
            $request->input('target_iban'),
            $request->input('target_name'),
        );

        return SponsorVoucherTransactionResource::create($fund->makePayout(
            $amount,
            $employee,
            $bankAccount,
            transactionNote: $request->input('note'),
        ));
    }

    /**
     * Display the specified resource.
     *
     * @param StorePayoutTransactionBatchRequest $request
     * @param Organization $organization
     * @return AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws RandomException
     * @noinspection PhpUnused
     */
    public function storePayoutBatch(
        StorePayoutTransactionBatchRequest $request,
        Organization $organization,
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('storePayoutsSponsor', [VoucherTransaction::class, $organization]);

        $fund = $organization->funds()->find($request->input('fund_id'));
        $batchId = $request->input('upload_batch_id') ?: VoucherTransaction::makeBatchUploadId();
        $employee = $request->employee($organization);

        $payouts = array_map(function ($payout) use ($fund, $employee, $batchId) {
            $amount = Arr::get($payout, 'amount_preset') ?
                $fund->amount_presets()->where('amount', Arr::get($payout, 'amount_preset'))->first() :
                Arr::get($payout, 'amount');

            $bankAccount = new BankAccount(
                Arr::get($payout, 'target_iban'),
                Arr::get($payout, 'target_name'),
            );

            return $fund->makePayout(
                $amount,
                $employee,
                $bankAccount,
                transactionFields: [ 'upload_batch_id' => $batchId],
                transactionNote: Arr::get($payout, 'note')
            );
        }, $request->input('payouts'));

        return SponsorVoucherTransactionResource::collection($payouts);
    }

    /**
     * Display the specified resource.
     *
     * @param StorePayoutTransactionBatchRequest $request
     * @param Organization $organization
     * @return JsonResponse
     */
    public function storePayoutBatchValidate(
        StorePayoutTransactionBatchRequest $request,
        Organization $organization,
    ): JsonResponse {
        $this->authorize('show', $organization);
        $this->authorize('storePayoutsSponsor', [VoucherTransaction::class, $organization]);

        return new JsonResponse($request->authorize() ?: []);
    }

    /**
     * Display the specified resource.
     *
     * @param UpdatePayoutTransactionRequest $request
     * @param Organization $organization
     * @param VoucherTransaction $transaction
     * @return SponsorVoucherTransactionResource
     */
    public function updatePayout(
        UpdatePayoutTransactionRequest $request,
        Organization $organization,
        VoucherTransaction $transaction,
    ): SponsorVoucherTransactionResource {
        $this->authorize('show', $organization);
        $this->authorize('updatePayoutsSponsor', [$transaction, $organization]);

        $employee = $request->employee($organization);

        $transaction->updatePayout($employee, $request->only([
            'amount', 'amount_preset_id', 'target_name', 'target_iban',
        ]));

        if ($request->input('skip_transfer_delay')) {
            $transaction->skipTransferDelay($employee);
        }

        if ($request->input('cancel')) {
            $transaction->cancelPending($employee, true);
        }

        return SponsorVoucherTransactionResource::create($transaction);
    }

    /**
     * @param StoreTransactionBatchRequest $request
     * @param Organization $organization
     * @return JsonResponse
     * @throws AuthorizationException
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
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function storeBatch(
        StoreTransactionBatchRequest $request,
        Organization $organization
    ) {
        $this->authorize('storeBatchAsSponsor', [VoucherTransaction::class, $organization]);

        $transactions = $request->input('transactions');
        $employee = $request->employee($organization);

        $index = 0;
        $createdItems = [];
        $errorsItems = [];

        while (count($transactions) > $index) {
            $slice = array_slice($transactions, $index++, 1, true);
            $item = array_first($slice);
            $validator = $request->validateRows($slice);

            if ($validator->passes()) {
                $voucher = Voucher::find(Arr::get($item, 'voucher_id'));

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

        return new JsonResponse([
            'created' => SponsorVoucherTransactionResource::queryCollection($query, (clone $query)->count()),
            'errors' => array_reduce($errorsItems, fn($array, $item) => array_merge($array, $item), []),
        ]);
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
        VoucherTransaction $voucherTransaction,
    ): SponsorVoucherTransactionResource {
        $this->authorize('show', $organization);
        $this->authorize('showSponsor', [$voucherTransaction, $organization]);

        return SponsorVoucherTransactionResource::create($voucherTransaction);
    }
}
