<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Resources\Provider\ProviderVoucherResource;
use App\Http\Resources\VoucherResource;
use App\Models\Voucher;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class VouchersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        return VoucherResource::collection(Voucher::getModel()->where(
            'identity_address', $request->get('identity')
        )->get());
    }

    /**
     * Display the specified resource.
     *
     * @param Voucher $voucher
     * @return VoucherResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Voucher $voucher)
    {
        $this->authorize('show', $voucher);

        return new VoucherResource($voucher);
    }

    /**
     * @param Voucher $voucher
     * @return ProviderVoucherResource
     */
    public function provider(Voucher $voucher) {
        return new ProviderVoucherResource($voucher);
    }
}
