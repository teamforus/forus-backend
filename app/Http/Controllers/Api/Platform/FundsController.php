<?php

namespace App\Http\Controllers\Api\Platform;

use App\Events\Vouchers\VoucherCreated;
use App\Http\Resources\FundResource;
use App\Http\Resources\VoucherResource;
use App\Models\Fund;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FundsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        return FundResource::collection(Fund::getModel()->where(
            'state', 'active'
        )->get());
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
     * @param Request $request
     * @param Fund $fund
     * @return VoucherResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function apply(Request $request, Fund $fund) {
        $identity = auth()->user()->getAuthIdentifier();

        // The same identity can't apply twice to the same fund
        if ($fund->vouchers()->where(
            'identity_address', $identity
        )->count() > 0) {
            return response()->json([
                'message' => e(trans('validation.fund.already_taken')),
                'key' => 'already_taken'
            ], 403);
        }

        $this->authorize('apply', $fund);

        $voucher = $fund->vouchers()->create([
            'amount' => Fund::amountForIdentity($fund, auth()->id()),
            'identity_address' => auth()->user()->getAuthIdentifier(),
        ]);

        VoucherCreated::dispatch($voucher);

        return new VoucherResource($voucher);
    }
}
