<?php

namespace App\Http\Controllers\Api\Platform;

use App\Events\Vouchers\VoucherCreated;
use App\Http\Requests\Api\Platform\Funds\StoreIdealBunqMeRequestRequest;
use App\Http\Resources\BunqIdealIssuerResource;
use App\Http\Resources\BunqMeIdealRequestResource;
use App\Http\Resources\FundResource;
use App\Http\Resources\VoucherResource;
use App\Models\Fund;
use App\Http\Controllers\Controller;
use App\Models\Implementation;
use App\Services\BunqService\BunqService;

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
            'expire_at' => $fund->end_date
        ]);

        VoucherCreated::dispatch($voucher);

        return new VoucherResource($voucher);
    }

    /**
     * @param Fund $fund
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function idealIssuers(Fund $fund) {
        $allowIssuers = env('BUNQ_IDEAL_USE_ISSUERS', true);

        return BunqIdealIssuerResource::collection(
            $allowIssuers ? BunqService::getIdealIssuers(
                $fund->fund_config->bunq_sandbox
            ) : collect()
        );
    }

    /**
     * @param StoreIdealBunqMeRequestRequest $request
     * @param Fund $fund
     * @return BunqMeIdealRequestResource
     * @throws \Exception
     */
    public function idealMakeRequest(
        StoreIdealBunqMeRequestRequest $request,
        Fund $fund
    ) {
        return new BunqMeIdealRequestResource($fund->makeBunqMeTab(
            $request->input('amount'),
            (string) $request->input('description'),
            $request->input('issuer', false)
        ));
    }
}
