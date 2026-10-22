<?php

namespace App\Services\MeAppApiService\Http\Controllers\Provider\Vouchers;

use App\Http\Controllers\Api\Platform\Vouchers\TransactionsController as LegacyTransactionsController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Vouchers\Transactions\StoreVoucherTransactionRequest;
use App\Models\VoucherToken;
use App\Services\MeAppApiService\Http\Resources\Provider\VoucherTransactionResource;
use Illuminate\Auth\Access\AuthorizationException;
use Throwable;

class TransactionsController extends Controller
{
    /**
     * Create a voucher payment.
     *
     * @param StoreVoucherTransactionRequest $request
     * @param VoucherToken $voucherToken
     * @throws AuthorizationException|Throwable
     * @return VoucherTransactionResource
     */
    public function store(
        StoreVoucherTransactionRequest $request,
        VoucherToken $voucherToken
    ): VoucherTransactionResource {
        $response = resolve(LegacyTransactionsController::class)->store($request, $voucherToken);

        return new VoucherTransactionResource($response->resource);
    }
}
