<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Requests\Api\Platform\Vouchers\ShareProductVoucherRequest;
use App\Http\Requests\Api\Platform\Vouchers\StoreProductVoucherRequest;
use App\Http\Resources\VoucherResource;
use App\Models\Product;
use App\Models\Voucher;
use App\Models\VoucherToken;
use App\Http\Controllers\Controller;
use App\Services\Forus\Record\Repositories\RecordRepo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
    public function index(): AnonymousResourceCollection {
        $this->authorize('viewAny', Voucher::class);

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
    ): VoucherResource {
        $this->authorize('store', Voucher::class);

        $product = Product::find($request->input('product_id'));
        $voucher = Voucher::findByAddress($request->input('voucher_address'), auth_address());

        $this->authorize('reserve', [$product, $voucher]);

        return new VoucherResource($voucher->buyProductVoucher($product)->load(
            VoucherResource::$load
        ));
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
    ): VoucherResource {
        $this->authorize('show', $voucherToken->voucher);

        return new VoucherResource(
            $voucherToken->voucher->load(VoucherResource::$load)
        );
    }

    /**
     * Send target voucher to user email.
     *
     * @param VoucherToken $voucherToken
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function sendEmail(
        VoucherToken $voucherToken
    ): void {
        $this->authorize('sendEmail', $voucherToken->voucher);

        $voucherToken->voucher->sendToEmail($this->recordRepo->primaryEmailByAddress(
            $voucherToken->voucher->identity_address
        ));
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
    ): void {
        $this->authorize('shareVoucher', $voucherToken->voucher);

        $voucherToken->voucher->shareVoucherEmail(
            $request->input('reason'),
            (bool) $request->get('send_copy', false)
        );
    }

    /**
     * @param VoucherToken $voucherToken
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function destroy(
        VoucherToken $voucherToken
    ): JsonResponse {
        $this->authorize('destroy', $voucherToken->voucher);

        return response()->json([
            'success' => $voucherToken->voucher->delete() === true
        ]);
    }
}
