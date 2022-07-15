<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Requests\Api\Platform\Funds\IndexFundsRequest;
use App\Http\Requests\Api\Platform\Funds\RedeemFundsRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\FundResource;
use App\Http\Resources\PrevalidationResource;
use App\Http\Resources\VoucherResource;
use App\Models\Fund;
use App\Http\Controllers\Controller;
use App\Models\Implementation;
use App\Models\Organization;
use App\Traits\ThrottleWithMeta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

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

        $organizations = Organization::whereIn('id', (clone $query)->select('organization_id'))->get();
        $organizations = $organizations->map(fn(Organization $item) => $item->only('id', 'name'));
        $meta = compact('organizations');
        $query->with(FundResource::load());

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

        return FundResource::create($fund);
    }

    /**
     * @param RedeemFundsRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function redeem(RedeemFundsRequest $request): JsonResponse
    {
        $vouchersAvailable = $request->getAvailableVouchers();

        if ($prevalidation = $request->getPrevalidation()) {
            $this->authorize('redeem', $prevalidation);
            $prevalidation->assignToIdentity($request->identity());
        }

        // check permissions of all voucher before assigning
        foreach ($vouchersAvailable as $voucher) {
            if (Gate::allows('redeem', $voucher)) {
                $voucher->assignToIdentity($request->identity());
            }
        }

        return new JsonResponse([
            'prevalidation' => $prevalidation ? PrevalidationResource::create($prevalidation) : null,
            'vouchers' => VoucherResource::collection($vouchersAvailable),
        ]);
    }

    /**
     * Apply fund for identity
     *
     * @param BaseFormRequest $request
     * @param Fund $fund
     * @return VoucherResource|null
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function apply(BaseFormRequest $request, Fund $fund): ?VoucherResource
    {
        $this->authorize('apply', [$fund, 'apply']);

        $voucher = $fund->makeVoucher($request->auth_address());

        $voucher = $voucher ?: $fund->vouchers()->where([
            'identity_address' => $request->auth_address(),
        ])->first();

        return $voucher ? VoucherResource::create($voucher) : null;
    }
}
