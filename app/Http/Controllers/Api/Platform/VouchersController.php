<?php

namespace App\Http\Controllers\Api\Platform;

use App\Events\Vouchers\VoucherCreated;
use App\Http\Requests\Api\Platform\Vouchers\ShareProductVoucherRequest;
use App\Http\Requests\Api\Platform\Vouchers\StoreProductVoucherRequest;
use App\Http\Resources\Provider\ProviderVoucherResource;
use App\Http\Resources\VoucherResource;
use App\Models\Implementation;
use App\Models\Product;
use App\Models\Voucher;
use App\Models\VoucherToken;
use App\Http\Controllers\Controller;
use App\Services\Forus\Identity\Repositories\IdentityRepo;
use App\Services\Forus\Record\Repositories\RecordRepo;

class VouchersController extends Controller
{
    private $recordRepo;

    public function __construct(RecordRepo $recordRepo)
    {
        $this->recordRepo = $recordRepo;
    }

    /**
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index() {
        $this->authorize('index', Voucher::class);

        return VoucherResource::collection(Voucher::query()->where([
            'identity_address' => auth_address()
        ])->get()->load(VoucherResource::$load));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreProductVoucherRequest $request
     * @return VoucherResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StoreProductVoucherRequest $request
    ) {
        $this->authorize('store', Voucher::class);

        $product = Product::find($request->input('product_id'));

        /** @var VoucherToken $voucherToken */
        $voucherToken = VoucherToken::query()->where([
            'address' => $request->input('voucher_address')
        ])->first() ?? abort(404);

        $voucher = $voucherToken->voucher ?? abort(404);

        $this->authorize('reserve', $product);

        $voucherExpireAt = $voucher->fund->end_date->gt(
            $product->expire_at
        ) ? $product->expire_at : $voucher->fund->end_date;

        $voucher = Voucher::create([
            'identity_address'  => auth_address(),
            'parent_id'         => $voucher->id,
            'fund_id'           => $voucher->fund_id,
            'product_id'        => $product->id,
            'amount'            => $product->getPriceByCurrency($voucher->fund->currency),
            'expire_at'         => $voucherExpireAt
        ]);

        VoucherCreated::dispatch($voucher);

        return new VoucherResource($voucher->load(VoucherResource::$load));
    }

    /**
     * Display the specified resource.
     *
     * @param VoucherToken $voucherToken
     * @return VoucherResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        VoucherToken $voucherToken
    ) {
        $this->authorize('show', $voucherToken->voucher);

        return new VoucherResource(
            $voucherToken->voucher->load(VoucherResource::$load)
        );
    }

    /**
     * @param VoucherToken $voucherToken
     * @return ProviderVoucherResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function provider(
        VoucherToken $voucherToken
    ) {
        $this->authorize('useAsProvider', $voucherToken->voucher);

        $voucherToken->voucher->setAttribute('address', $voucherToken->address);

        return new ProviderVoucherResource($voucherToken->voucher);
    }

    /**
     * Send target voucher to user email.
     *
     * @param VoucherToken $voucherToken
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function sendEmail(
        VoucherToken $voucherToken
    ) {
        $this->authorize('show', $voucherToken->voucher);

        $voucher = $voucherToken->voucher;

        $email = $this->recordRepo->primaryEmailByAddress(
            $voucher->identity_address
        );

        $voucher->sendToEmail($email);
    }

    /**
     * Share product voucher to email.
     *
     * @param VoucherToken $voucherToken
     * @param ShareProductVoucherRequest $request
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function shareVoucher(
        VoucherToken $voucherToken,
        ShareProductVoucherRequest $request
    ) {
        $this->authorize('show', $voucherToken->voucher);

        $voucherToken->voucher->shareVoucherEmail(
            $request->input('reason'),
            (bool)$request->get('send_copy', false)
        );
    }

    /**
     * @param VoucherToken $voucherToken
     * @return array
     * @throws \Exception
     */
    public function destroy(
        VoucherToken $voucherToken
    ) {
        $this->authorize('destroy', $voucherToken->voucher);

        return [
            'success' => $voucherToken->voucher->delete() === true
        ];
    }
}
