<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Payouts\IndexPayoutsRequest;
use App\Http\Requests\Api\Platform\Payouts\StorePayoutRequest;
use App\Http\Resources\VoucherTransactionPayoutResource;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Rules\Payouts\VoucherPayoutAmountRule;
use App\Rules\Payouts\VoucherPayoutCountRule;
use App\Scopes\Builders\VoucherTransactionQuery;
use App\Searches\VoucherTransactionsSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class PayoutsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexPayoutsRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(IndexPayoutsRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [VoucherTransaction::class]);

        $query = VoucherTransaction::where(function (Builder $builder) use ($request) {
            $builder->where('target', VoucherTransaction::TARGET_PAYOUT);
            $builder->whereRelation('voucher', 'identity_id', $request->auth_id());

            $builder->where(function (Builder $query) use ($request) {
                $query->where(function (Builder $query) use ($request) {
                    $query->where('initiator', VoucherTransaction::INITIATOR_REQUESTER);
                    $query->whereRelation('voucher', 'identity_id', $request->auth_id());
                });

                $query->orWhereHas('voucher', function (Builder $query) use ($request) {
                    $query->where('voucher_type', Voucher::VOUCHER_TYPE_PAYOUT);
                    $query->whereRelation('fund_request', 'identity_id', $request->auth_id());
                });
            });
        });

        return VoucherTransactionPayoutResource::queryCollection(
            (new VoucherTransactionsSearch($request->only('q'), $query))->query(),
            $request,
        );
    }

    /**
     * Store a newly created payout.
     *
     * @param StorePayoutRequest $request
     * @throws Throwable
     * @return VoucherTransactionPayoutResource
     */
    public function store(StorePayoutRequest $request): VoucherTransactionPayoutResource
    {
        /** @var Voucher $voucher */
        $voucher = $request->voucher();

        $this->authorize('storePayoutRequester', [VoucherTransaction::class, $voucher]);

        return DB::transaction(static function () use ($request, $voucher) {
            $amount = (float) $request->input('amount');

            $voucher = Voucher::query()
                ->whereKey($voucher->id)
                ->lockForUpdate()
                ->firstOrFail();

            $voucher->loadMissing('fund_request.records');

            $payoutLimit = $voucher->fund?->fund_config?->allow_voucher_payout_count;

            if ($payoutLimit !== null) {
                if (VoucherTransactionQuery::countRequesterPayouts($voucher, true) >= (int) $payoutLimit) {
                    throw ValidationException::withMessages([
                        'amount' => [VoucherPayoutCountRule::countReachedMessage((int) $payoutLimit)],
                    ]);
                }
            }

            if (VoucherPayoutAmountRule::exceedsBalance($amount, (float) $voucher->amount_available)) {
                throw ValidationException::withMessages([
                    'amount' => [VoucherPayoutAmountRule::balanceExceededMessage($voucher)],
                ]);
            }

            $transaction = $voucher->makeTransaction([
                'initiator' => VoucherTransaction::INITIATOR_REQUESTER,
                'target' => VoucherTransaction::TARGET_PAYOUT,
                'target_iban' => $voucher->fund_request->getIban(false),
                'target_name' => $voucher->fund_request->getIbanName(false),
                'amount' => $amount,
            ]);

            return VoucherTransactionPayoutResource::create($transaction);
        });
    }
}
