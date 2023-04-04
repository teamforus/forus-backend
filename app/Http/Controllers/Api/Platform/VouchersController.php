<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Requests\Api\Platform\Vouchers\IndexVouchersRequest;
use App\Http\Requests\Api\Platform\Vouchers\DeactivateVoucherRequest;
use App\Http\Requests\Api\Platform\Vouchers\ShareProductVoucherRequest;
use App\Http\Resources\VoucherCollectionResource;
use App\Http\Resources\VoucherResource;
use App\Models\FundConfig;
use App\Models\Voucher;
use App\Models\VoucherToken;
use App\Http\Controllers\Controller;
use App\Searches\VouchersSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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

        $query = Voucher::query()
            ->where('identity_address', $request->auth_address())
            ->whereDoesntHave('product_reservation');

        $search = new VouchersSearch($request->only([
            'type', 'state', 'archived', 'allow_reimbursements',
            'implementation_id', 'implementation_key', 'product_id',
            'order_by', 'order_dir',
        ]), $query);

        if ($request->isMeApp()) {
            $query->whereRelation('fund.fund_config', [
                'vouchers_type' => FundConfig::VOUCHERS_TYPE_INTERNAL,
            ]);
        }

        // todo: remove fallback pagination 1000, when apps are ready
        return VoucherCollectionResource::queryCollection(
            $search->query(),
            $request->input('per_page', 1000)
        );
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

        return VoucherResource::create($voucherToken->voucher);
    }

    /**
     * Send target voucher to user email.
     *
     * @param VoucherToken $voucherToken
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function sendEmail(VoucherToken $voucherToken): JsonResponse
    {
        $this->authorize('sendEmail', $voucherToken->voucher);

        $voucher = $voucherToken->voucher;
        $voucher->sendToEmail($voucher->identity->email);

        return new JsonResponse([]);
    }

    /**
     * Share product voucher to email.
     *
     * @param VoucherToken $voucherToken
     * @param ShareProductVoucherRequest $request
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function shareVoucher(
        VoucherToken $voucherToken,
        ShareProductVoucherRequest $request
    ): JsonResponse {
        $this->authorize('shareVoucher', $voucherToken->voucher);

        $reason = $request->input('reason');
        $sendCopy = (bool) $request->get('send_copy', false);

        $voucherToken->voucher->shareVoucherEmail($reason, $sendCopy);

        return new JsonResponse([]);
    }

    /**
     * Deactivate voucher
     *
     * @param DeactivateVoucherRequest $request
     * @param VoucherToken $voucherToken
     * @return VoucherResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function deactivate(
        DeactivateVoucherRequest $request,
        VoucherToken $voucherToken
    ): VoucherResource {
        $this->authorize('deactivateRequester', $voucherToken->voucher);
        $voucherToken->voucher->deactivate($request->input('note') ?: '');

        return VoucherResource::create($voucherToken->voucher);
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

        return new JsonResponse([
            'success' => $voucherToken->voucher->delete() === true
        ]);
    }
}
