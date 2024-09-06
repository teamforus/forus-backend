<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Payouts\IndexPayoutTransactionsRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Payouts\StorePayoutTransactionBatchRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Payouts\StorePayoutTransactionRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Payouts\UpdatePayoutTransactionRequest;
use App\Http\Resources\Sponsor\VoucherTransactionPayoutResource;
use App\Models\Data\BankAccount;
use App\Models\Organization;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\VoucherTransactionQuery;
use App\Statistics\Funds\FinancialStatisticQueries;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Random\RandomException;

/**
 * @noinspection PhpUnused
 */
class PayoutsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexPayoutTransactionsRequest $request
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function index(
        IndexPayoutTransactionsRequest $request,
        Organization $organization,
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAnyPayoutsSponsor', [VoucherTransaction::class, $organization]);

        $query = (new FinancialStatisticQueries())->getFilterTransactionsQuery($organization, [
            ...$request->only([
                'state', 'fund_id', 'fund_state', 'amount_min', 'amount_max',
                'non_cancelable_from', 'non_cancelable_to',
            ]),
            'date_from' => $request->input('from') ? Carbon::parse($request->input('from'))->startOfDay() : null,
            'date_to' => $request->input('to') ? Carbon::parse($request->input('to'))->endOfDay() : null,
            'target' => VoucherTransaction::TARGET_PAYOUT,
            'initiator' => VoucherTransaction::INITIATOR_SPONSOR,
        ], VoucherTransaction::searchSponsor($request, $organization));

        return VoucherTransactionPayoutResource::queryCollection(VoucherTransactionQuery::order(
            $query,
            $request->input('order_by'),
            $request->input('order_dir')
        ));
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param VoucherTransaction $voucherTransaction
     * @return VoucherTransactionPayoutResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function show(
        Organization $organization,
        VoucherTransaction $voucherTransaction,
    ): VoucherTransactionPayoutResource {
        $this->authorize('show', $organization);
        $this->authorize('showPayoutSponsor', [$voucherTransaction, $organization]);

        return VoucherTransactionPayoutResource::create($voucherTransaction);
    }

    /**
     * Display the specified resource.
     *
     * @param StorePayoutTransactionRequest $request
     * @param Organization $organization
     * @return VoucherTransactionPayoutResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function store(
        StorePayoutTransactionRequest $request,
        Organization $organization,
    ): VoucherTransactionPayoutResource {
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

        $transaction = $fund->makePayout($amount, $employee, $bankAccount, transactionFields: [
            'description' => $request->input('description'),
        ]);

        if ($request->input('bsn') && $organization->bsn_enabled) {
            $transaction->addPayoutRelation('bsn', $request->input('bsn'));
        }

        if ($request->input('email')) {
            $transaction->addPayoutRelation('email', $request->input('email'));
        }

        return VoucherTransactionPayoutResource::create($transaction);
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
    public function storeBatch(
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

            $transaction = $fund->makePayout(
                $amount,
                $employee,
                bankAccount: new BankAccount(
                    Arr::get($payout, 'target_iban'),
                    Arr::get($payout, 'target_name'),
                ),
                transactionFields: [
                    'description' => Arr::get($payout, 'description'),
                    'upload_batch_id' => $batchId,
                ],
            );

            if (Arr::get($payout, 'bsn')) {
                $transaction->addPayoutRelation('bsn', Arr::get($payout, 'bsn'));
            }

            if (Arr::get($payout, 'email')) {
                $transaction->addPayoutRelation('email', Arr::get($payout, 'email'));
            }

            return $transaction;
        }, $request->input('payouts'));

        return VoucherTransactionPayoutResource::collection($payouts);
    }

    /**
     * Display the specified resource.
     *
     * @param StorePayoutTransactionBatchRequest $request
     * @param Organization $organization
     * @return JsonResponse
     */
    public function storeBatchValidate(
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
     * @return VoucherTransactionPayoutResource
     */
    public function update(
        UpdatePayoutTransactionRequest $request,
        Organization $organization,
        VoucherTransaction $transaction,
    ): VoucherTransactionPayoutResource {
        $this->authorize('show', $organization);
        $this->authorize('updatePayoutsSponsor', [$transaction, $organization]);

        $employee = $request->employee($organization);

        $transaction->updatePayout($employee, $request->only([
            'amount', 'amount_preset_id', 'target_name', 'target_iban', 'description',
        ]));

        if ($request->input('skip_transfer_delay')) {
            $transaction->skipTransferDelay($employee);
        }

        if ($request->input('cancel')) {
            $transaction->cancelPending($employee, true);
        }

        return VoucherTransactionPayoutResource::create($transaction);
    }
}
