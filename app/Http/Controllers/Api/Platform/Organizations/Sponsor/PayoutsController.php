<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Payouts\CancelPayoutTransactionRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Payouts\IndexPayoutBankAccountsRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Payouts\IndexPayoutTransactionsRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Payouts\StorePayoutTransactionBatchRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Payouts\StorePayoutTransactionRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Payouts\UpdatePayoutTransactionRequest;
use App\Http\Resources\Sponsor\SponsorPayoutBankAccountResource;
use App\Http\Resources\Sponsor\VoucherTransactionPayoutResource;
use App\Models\Data\BankAccount;
use App\Models\Organization;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\VoucherQuery;
use App\Scopes\Builders\VoucherTransactionQuery;
use App\Searches\Sponsor\PayoutBankAccounts\FundRequestPayoutBankAccountSearch;
use App\Searches\Sponsor\PayoutBankAccounts\PayoutTransactionPayoutBankAccountSearch;
use App\Searches\Sponsor\PayoutBankAccounts\ProfilePayoutBankAccountSearch;
use App\Searches\Sponsor\PayoutBankAccounts\ReimbursementPayoutBankAccountSearch;
use App\Searches\VoucherTransactionsSearch;
use App\Statistics\Funds\FinancialStatisticQueries;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Random\RandomException;
use Throwable;

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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return AnonymousResourceCollection
     * @noinspection PhpUnused
     */
    public function index(
        IndexPayoutTransactionsRequest $request,
        Organization $organization,
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAnyPayoutsSponsor', [VoucherTransaction::class, $organization]);

        $search = new VoucherTransactionsSearch($request->only([
            'q', 'targets', 'state', 'from', 'to', 'amount_min', 'amount_max',
            'transfer_in_min', 'transfer_in_max', 'fund_state', 'fund_id',
            'voucher_transaction_bulk_id', 'voucher_id', 'pending_bulking',
            'reservation_voucher_id', 'non_cancelable_from', 'non_cancelable_to', 'bulk_state',
            'identity_address', 'execution_date_from', 'execution_date_to',
        ]), VoucherTransaction::query());

        $query = (new FinancialStatisticQueries())->getFilterTransactionsQuery($organization, [
            ...$request->only([
                'state', 'fund_id', 'fund_state', 'amount_min', 'amount_max',
                'non_cancelable_from', 'non_cancelable_to',
            ]),
            'date_from' => $request->input('from') ? Carbon::parse($request->input('from'))->startOfDay() : null,
            'date_to' => $request->input('to') ? Carbon::parse($request->input('to'))->endOfDay() : null,
            'targets' => [VoucherTransaction::TARGET_PAYOUT],
        ], $search->searchSponsor($organization));

        $query->whereIn('initiator', [
            VoucherTransaction::INITIATOR_SPONSOR,
            VoucherTransaction::INITIATOR_REQUESTER,
        ]);

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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return VoucherTransactionPayoutResource
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
     * @noinspection PhpUnused
     * @throws AuthorizationException|Throwable
     * @return VoucherTransactionPayoutResource
     */
    public function store(
        StorePayoutTransactionRequest $request,
        Organization $organization,
    ): VoucherTransactionPayoutResource {
        $this->authorize('show', $organization);
        $this->authorize('storePayoutsSponsor', [VoucherTransaction::class, $organization]);

        $fund = $organization->funds()->find($request->input('fund_id'));
        $employee = $request->employee($organization);

        $transaction = DB::transaction(function () use ($request, $organization, $fund, $employee) {
            if ($request->isVoucherBackedPayout()) {
                $bankData = $request->bankAccountPayload();

                $voucher = VoucherQuery::whereEligibleForSponsorPayout(Voucher::query())
                    ->whereKey((int) $request->input('voucher_id'))
                    ->where('fund_id', $fund->id)
                    ->where('identity_id', $bankData['source_identity_id'] ?? null)
                    ->lockForUpdate()
                    ->first();

                if (!$voucher) {
                    throw ValidationException::withMessages([
                        'voucher_id' => [trans('validation.in', ['attribute' => 'voucher_id'])],
                    ]);
                }

                $payoutAmount = $request->resolvePayoutAmount($fund);
                $amount = $payoutAmount->getAmount();

                if ($payoutAmount->exceeds($voucher->amount_available)) {
                    throw ValidationException::withMessages([
                        $payoutAmount->getField() => [trans('validation.voucher.not_enough_funds')],
                    ]);
                }

                $transaction = $voucher->makeTransactionBySponsor($employee, [
                    'amount' => $amount,
                    'amount_voucher' => $amount,
                    'target' => VoucherTransaction::TARGET_PAYOUT,
                    'target_iban' => $bankData['target_iban'],
                    'target_name' => $bankData['target_name'],
                    'transfer_at' => now()->addDay(),
                    'description' => $request->input('description'),
                    ...Arr::only($bankData, ['target_source_type', 'target_source_id']),
                ]);
            } else {
                $bankData = $request->bankAccountData();
                $bankAccount = new BankAccount($bankData['target_iban'] ?? null, $bankData['target_name'] ?? null);
                $payoutAmount = $request->resolvePayoutAmount($fund);
                $amount = $payoutAmount->getPreset() ?? $payoutAmount->getAmount();

                $transaction = $fund->makePayout(null, $amount, $employee, $bankAccount, transactionFields: [
                    'description' => $request->input('description'),
                    ...Arr::only($bankData, ['target_source_type', 'target_source_id']),
                ]);
            }

            if ($request->input('bsn') && $organization->bsn_enabled) {
                $transaction->addPayoutRelation('bsn', $request->input('bsn'));
            }

            if ($request->input('email')) {
                $transaction->addPayoutRelation('email', $request->input('email'));
            }

            return $transaction;
        });

        return VoucherTransactionPayoutResource::create($transaction);
    }

    /**
     * Display the specified resource.
     *
     * @param StorePayoutTransactionBatchRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws RandomException
     * @return AnonymousResourceCollection
     * @noinspection PhpUnused
     */
    public function storeBatch(
        StorePayoutTransactionBatchRequest $request,
        Organization $organization,
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('storePayoutsSponsor', [VoucherTransaction::class, $organization]);

        $file = $request->post('file');
        $fund = $organization->funds()->find($request->input('fund_id'));
        $payouts = $request->input('payouts');
        $batchId = $request->input('upload_batch_id') ?: VoucherTransaction::makeBatchUploadId();
        $employee = $request->employee($organization);

        $event = $employee->logCsvUpload($employee::EVENT_UPLOADED_PAYOUTS, $file, $payouts);

        $payouts = array_map(function ($payout) use ($fund, $employee, $batchId) {
            $amount = Arr::get($payout, 'amount_preset') ?
                $fund->amount_presets()->where('amount', Arr::get($payout, 'amount_preset'))->first() :
                Arr::get($payout, 'amount');

            $transaction = $fund->makePayout(
                identity: null,
                amount: $amount,
                employee: $employee,
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
        }, $payouts);

        $event->forceFill([
            'data->uploaded_file_meta->state' => 'success',
            'data->uploaded_file_meta->created_ids' => Arr::pluck($payouts, 'id'),
        ])->update();

        return VoucherTransactionPayoutResource::createCollection($payouts);
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
     * @throws AuthorizationException|Throwable
     * @return VoucherTransactionPayoutResource
     */
    public function update(
        UpdatePayoutTransactionRequest $request,
        Organization $organization,
        VoucherTransaction $transaction,
    ): VoucherTransactionPayoutResource {
        $this->authorize('show', $organization);

        $employee = $request->employee($organization);

        $transaction = DB::transaction(function () use ($request, $transaction, $organization, $employee) {
            $this->authorize('updatePayoutsSponsor', [$transaction, $organization]);

            $transaction->updatePayout($employee, $request->only([
                'amount', 'amount_preset_id', 'target_name', 'target_iban', 'description',
            ]));

            if ($request->boolean('skip_transfer_delay')) {
                $transaction->skipTransferDelay($employee);
            }

            return $transaction;
        });

        return VoucherTransactionPayoutResource::create($transaction);
    }

    /**
     * Cancel the specified payout.
     *
     * @param CancelPayoutTransactionRequest $request
     * @param Organization $organization
     * @param VoucherTransaction $transaction
     * @throws AuthorizationException|Throwable
     * @return VoucherTransactionPayoutResource
     */
    public function cancel(
        CancelPayoutTransactionRequest $request,
        Organization $organization,
        VoucherTransaction $transaction,
    ): VoucherTransactionPayoutResource {
        $this->authorize('show', $organization);

        $employee = $request->employee($organization);

        $transaction = DB::transaction(function () use ($transaction, $organization, $employee) {
            $transaction->voucher()->lockForUpdate()->firstOrFail();
            $transaction = VoucherTransaction::query()->lockForUpdate()->findOrFail($transaction->id);

            $this->authorize('cancelPayoutsSponsor', [$transaction, $organization]);

            return $transaction->cancelPending($employee, true);
        });

        return VoucherTransactionPayoutResource::create($transaction);
    }

    /**
     * Display available payout bank accounts.
     *
     * @param IndexPayoutBankAccountsRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return AnonymousResourceCollection
     * @noinspection PhpUnused
     */
    public function bankAccounts(
        IndexPayoutBankAccountsRequest $request,
        Organization $organization,
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAnyPayoutBankAccountsSponsor', [VoucherTransaction::class, $organization]);

        $filters = $request->only([
            'q', 'identity_id',
        ]);

        $query = match ($request->input('type')) {
            'fund_request' => (new FundRequestPayoutBankAccountSearch($organization, $filters))
                ->query(),
            'profile_bank_account' => (new ProfilePayoutBankAccountSearch($organization, $filters))
                ->query()
                ->with('profile:id,identity_id'),
            'reimbursement' => (new ReimbursementPayoutBankAccountSearch($organization, $filters))
                ->query()
                ->with('voucher:id,identity_id'),
            'payout' => (new PayoutTransactionPayoutBankAccountSearch($organization, $filters))
                ->query()
                ->with('voucher:id,identity_id'),
            default => throw new InvalidArgumentException("Invalid type: {$request->input('type')}"),
        };

        return SponsorPayoutBankAccountResource::queryCollection($query->latest('created_at'), $request);
    }
}
