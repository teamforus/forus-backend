<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Requests\Api\Platform\Vouchers\StoreProductVoucherRequest;
use App\Http\Resources\Provider\ProviderVoucherResource;
use App\Http\Resources\VoucherResource;
use App\Models\Product;
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
        return VoucherResource::collection(Voucher::getModel()->where([
            'identity_address' => auth()->user()->getAuthIdentifier()
        ])->get());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreProductVoucherRequest $request
     * @return VoucherResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(StoreProductVoucherRequest $request)
    {
        $this->authorize('store', Voucher::class);

        /** @var Product $product */
        /** @var Voucher $voucher */
        $product = Product::query()->find($request->input('product_id'));
        $voucher = Voucher::query()->where([
            'address' => $request->input('voucher_address')
        ])->first();

        return new VoucherResource(Voucher::create([
            'identity_address'  => auth()->user()->getAuthIdentifier(),
            'parent_id'         => $voucher->id,
            'fund_id'           => $voucher->fund_id,
            'product_id'        => $product->id,
            'amount'            => $product->price,
            'address'           => app()->make('token_generator')->address()
        ]));
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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function provider(Voucher $voucher) {
        $this->authorize('useAsProvider', $voucher);

        return new ProviderVoucherResource($voucher);
    }
}
