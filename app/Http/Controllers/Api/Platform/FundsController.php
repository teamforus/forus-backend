<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Requests\Api\Platform\Funds\IndexFundsRequest;
use App\Http\Requests\Api\Platform\Funds\RedeemFundsRequest;
use App\Http\Resources\FundResource;
use App\Http\Resources\PrevalidationResource;
use App\Http\Resources\VoucherResource;
use App\Models\Fund;
use App\Http\Controllers\Controller;
use App\Models\Implementation;
use App\Traits\ThrottleWithMeta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Class FundsController
 * @package App\Http\Controllers\Api\Platform
 */
class FundsController extends Controller
{
    use ThrottleWithMeta;

    /**
     * FundsController constructor.
     */
    public function __construct()
    {
        $this->maxAttempts = env('ACTIVATION_CODE_ATTEMPTS', 3);
        $this->decayMinutes = env('ACTIVATION_CODE_DECAY', 180);
    }

    /**
     * Display a listing of all active funds.
     *
     * @param IndexFundsRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(IndexFundsRequest $request): AnonymousResourceCollection
    {
        $state = $request->input('state') === 'active_paused_and_closed' ? [
            Fund::STATE_CLOSED,
            Fund::STATE_PAUSED,
            Fund::STATE_ACTIVE,
        ] : Fund::STATE_ACTIVE;

        $query = Fund::search($request->only([
            'tag', 'tag_id', 'organization_id', 'fund_id', 'q', 'implementation_id',
            'order_by', 'order_by_dir', 'with_external',
        ]), Implementation::queryFundsByState($state));

        $meta = [
            'organizations' => $query->with('organization')->get()->pluck(
                'organization.name', 'organization.id'
            )->map(static function ($name, $id) {
                return (object) compact('id', 'name');
            })->toArray()
        ];

        if ($per_page = $request->input('per_page', false)) {
            return FundResource::collection($query->paginate($per_page))->additional(compact('meta'));
        }

        return FundResource::collection($query->get());
    }

    /**
     * Display the specified resource.
     *
     * @param Fund $fund
     * @return FundResource
     */
    public function show(Fund $fund): FundResource {
        if (!in_array($fund->state, [
            Fund::STATE_ACTIVE,
            Fund::STATE_PAUSED,
            Fund::STATE_CLOSED
        ], true)) {
            abort(404);
        }

        return new FundResource($fund);
    }

    /**
     * @param RedeemFundsRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function redeem(RedeemFundsRequest $request): JsonResponse
    {
        $vouchersAvailable = $request->getAvailableVouchers();

        if ($prevalidation = $request->getPrevalidation()) {
            $this->authorize('redeem', $prevalidation);
            $prevalidation->assignToIdentity($request->auth_address());
        }

        // check permissions of all voucher before assigning
        foreach ($vouchersAvailable as $voucher) {
            $this->authorize('redeem', $voucher);
        }

        foreach ($vouchersAvailable as $voucher) {
            $voucher->assignToIdentity($request->auth_address());
        }

        return new JsonResponse([
            'prevalidation' => $prevalidation ? PrevalidationResource::create($prevalidation) : null,
            'vouchers' => VoucherResource::collection($vouchersAvailable),
        ]);
    }

    /**
     * Apply fund for identity
     *
     * @param Fund $fund
     * @return VoucherResource|null
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function apply(Fund $fund): ?VoucherResource
    {
        $this->authorize('apply', [$fund, 'apply']);

        $identity_address = auth_address();
        $voucher = $fund->makeVoucher($identity_address);
        $formulaProductVouchers = $fund->makeFundFormulaProductVouchers($identity_address);

        $voucher = $voucher ?: array_first($formulaProductVouchers) ?: $fund->vouchers()->where([
            'identity_address' => $identity_address,
        ])->first();

        return $voucher ? new VoucherResource($voucher) : null;
    }
}
