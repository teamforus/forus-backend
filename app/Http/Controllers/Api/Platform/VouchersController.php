<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Vouchers\DeactivateVoucherRequest;
use App\Http\Requests\Api\Platform\Vouchers\IndexVouchersRequest;
use App\Http\Requests\Api\Platform\Vouchers\ShareProductVoucherRequest;
use App\Http\Resources\VoucherCollectionResource;
use App\Http\Resources\VoucherResource;
use App\Models\FundConfig;
use App\Models\Voucher;
use App\Searches\VouchersSearch;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Throwable;

class VouchersController extends Controller
{
    /**
     * @param IndexVouchersRequest $request
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(IndexVouchersRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Voucher::class);

        $query = Voucher::query()
            ->where('identity_id', $request->auth_id())
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
     * @param Voucher $voucher
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return VoucherResource
     */
    public function show(Voucher $voucher): VoucherResource
    {
        $this->authorize('show', $voucher);

        return VoucherResource::create($voucher);
    }

    /**
     * Send target voucher to user email.
     *
     * @param Voucher $voucher
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return JsonResponse
     * @noinspection PhpUnused
     */
    public function sendEmail(Voucher $voucher): JsonResponse
    {
        $this->authorize('sendEmail', $voucher);

        $voucher->sendToEmail($voucher->identity->email);

        return new JsonResponse([]);
    }

    /**
     * Share product voucher to email.
     *
     * @param ShareProductVoucherRequest $request
     * @param Voucher $voucher
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return JsonResponse
     * @noinspection PhpUnused
     */
    public function shareVoucher(
        ShareProductVoucherRequest $request,
        Voucher $voucher,
    ): JsonResponse {
        $this->authorize('shareVoucher', $voucher);

        $reason = $request->input('reason');
        $sendCopy = (bool) $request->get('send_copy', false);

        $voucher->shareVoucherEmail($reason, $sendCopy);

        return new JsonResponse([]);
    }

    /**
     * Deactivate voucher.
     *
     * @param DeactivateVoucherRequest $request
     * @param Voucher $voucher
     * @throws \Illuminate\Auth\Access\AuthorizationException|Throwable
     * @return VoucherResource
     */
    public function deactivate(
        DeactivateVoucherRequest $request,
        Voucher $voucher,
    ): VoucherResource {
        $this->authorize('deactivateRequester', $voucher);
        $voucher->deactivate($request->input('note') ?: '');

        return VoucherResource::create($voucher);
    }

    /**
     * @param Voucher $voucher
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws Exception
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Voucher $voucher): JsonResponse
    {
        $this->authorize('destroy', $voucher);

        return new JsonResponse([
            'success' => $voucher->delete() === true,
        ]);
    }
}
