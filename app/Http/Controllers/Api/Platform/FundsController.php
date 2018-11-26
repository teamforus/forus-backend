<?php

namespace App\Http\Controllers\Api\Platform;

use App\Events\Vouchers\VoucherCreated;
use App\Http\Resources\FundResource;
use App\Http\Resources\VoucherResource;
use App\Models\Fund;
use App\Http\Controllers\Controller;
use App\Models\Implementation;
use Illuminate\Http\Request;

class FundsController extends Controller
{
    /**
     * Display a listing of all active funds.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        return FundResource::collection(Implementation::activeFunds());
    }

    /**
     * Display the specified resource.
     *
     * @param Fund $fund
     * @return FundResource
     */
    public function show(Fund $fund)
    {
        if ($fund->state != 'active') {
            return abort(404);
        }

        return new FundResource($fund);
    }

    /**
     * Apply fund for identity
     *
     * @param Fund $fund
     * @return VoucherResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function apply(
        Fund $fund
    ) {
        $this->authorize('apply', $fund);

        $voucher = $fund->vouchers()->create([
            'amount' => Fund::amountForIdentity($fund, auth()->id()),
            'identity_address' => auth()->user()->getAuthIdentifier(),
        ]);

        VoucherCreated::dispatch($voucher);

        return new VoucherResource($voucher);
    }
}
