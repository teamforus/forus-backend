<?php

namespace App\Http\Controllers\Api\Platform;

use App\Events\Vouchers\VoucherCreated;
use App\Http\Requests\Api\Platform\Vouchers\StoreProductVoucherRequest;
use App\Http\Resources\Provider\ProviderVoucherResource;
use App\Http\Resources\VoucherResource;
use App\Models\Product;
use App\Models\Voucher;
use App\Models\VoucherToken;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class VouchersController extends Controller
{
    protected $identityRepo;
    protected $recordRepo;

    /**
     * VouchersController constructor.
     */
    public function __construct() {
        $this->identityRepo = app()->make('forus.services.identity');
        $this->recordRepo = app()->make('forus.services.record');
    }

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

        $this->authorize('reserve', $product);

        $product->updateSoldOutState();

        $voucher = Voucher::create([
            'identity_address'  => auth()->user()->getAuthIdentifier(),
            'parent_id'         => $voucher->id,
            'fund_id'           => $voucher->fund_id,
            'product_id'        => $product->id,
            'amount'            => $product->price
        ]);

        VoucherCreated::dispatch($voucher);

        return new VoucherResource($voucher);
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
    public function provider(
        Voucher $voucher
    ) {
        $this->authorize('useAsProvider', $voucher);

        return new ProviderVoucherResource($voucher);
    }

    /**
     * Send target voucher to user email.
     *
     * @param Voucher $voucher
     */
    public function sendEmail(
        Voucher $voucher
    ) {
        /** @var VoucherToken $voucherToken */
        $voucherToken = $voucher->tokens()->where([
            'need_confirmation' => false
        ])->first();

        if ($voucher->type == 'product') {
            $fund_product_name = $voucher->product->name;
        } else {
            $fund_product_name = $voucher->fund->name;
        }

        resolve('forus.services.mail_notification')->sendVoucher(
            auth()->user()->getAuthIdentifier(),
            $fund_product_name,
            $voucherToken->getQrCodeUrl()
        );
    }
}
