<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Requests\Api\Platform\Vouchers\IndexVouchersRequest;
use App\Http\Requests\Api\Platform\Vouchers\ShareProductVoucherRequest;
use App\Http\Requests\Api\Platform\Vouchers\StoreProductReservationRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\VoucherCollectionResource;
use App\Http\Resources\VoucherResource;
use App\Models\Voucher;
use App\Models\VoucherToken;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Class VouchersController
 * @package App\Http\Controllers\Api\Platform
 */
class VouchersController extends Controller
{
    /**
     * @param IndexVouchersRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(IndexVouchersRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Voucher::class);

        $query = Voucher::whereIdentityAddress($request->auth_address())
            ->whereDoesntHave('product_reservation')
            ->orderByDesc('created_at');

        if ($request->input('type') === Voucher::TYPE_BUDGET) {
            $query->whereNull('product_id');
        }

        if ($request->input('type') === Voucher::TYPE_PRODUCT) {
            $query->whereNotNull('product_id');
        }

        // todo: remove fallback pagination 1000, when apps are ready
        $vouchers = $query
            ->with(VoucherCollectionResource::load())
            ->withCount(VoucherCollectionResource::load_count())
            ->paginate($request->input('per_page', 1000));

        return VoucherCollectionResource::collection($vouchers);
    }

    /**
     * Display the specified resource.
     *
     * @param VoucherToken $voucherToken
     * @return VoucherResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(VoucherToken $voucherToken): VoucherResource
    {
        $this->authorize('show', $voucherToken->voucher);

        return new VoucherResource($voucherToken->voucher->load(VoucherResource::load()));
    }

    /**
     * Send target voucher to user email.
     *
     * @param BaseFormRequest $request
     * @param VoucherToken $voucherToken
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function sendEmail(BaseFormRequest $request, VoucherToken $voucherToken): JsonResponse
    {
        $this->authorize('sendEmail', $voucherToken->voucher);

        $voucherToken->voucher->sendToEmail($request->records_repo()->primaryEmailByAddress(
            $voucherToken->voucher->identity_address
        ));

        return response()->json([]);
    }

    /**
     * Share product voucher to email.
     *
     * @param VoucherToken $voucherToken
     * @param ShareProductVoucherRequest $request
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function shareVoucher(
        VoucherToken $voucherToken,
        ShareProductVoucherRequest $request
    ): JsonResponse {
        $this->authorize('shareVoucher', $voucherToken->voucher);

        $reason = $request->input('reason');
        $sendCopy = (bool) $request->get('send_copy', false);

        $voucherToken->voucher->shareVoucherEmail($reason, $sendCopy);

        return response()->json([]);
    }

    /**
     * @param VoucherToken $voucherToken
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function destroy(VoucherToken $voucherToken): JsonResponse
    {
        $this->authorize('destroy', $voucherToken->voucher);

        return response()->json([
            'success' => $voucherToken->voucher->delete() === true
        ]);
    }
}
