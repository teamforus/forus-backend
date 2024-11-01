<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Requests\Api\Platform\Funds\CheckFundRequest;
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
use App\Models\Prevalidation;
use App\Models\Voucher;
use App\Searches\FundSearch;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Exception;

class FundsController extends Controller
{
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

        $query = (new FundSearch($request->only([
            'tag', 'tag_id', 'organization_id', 'fund_id', 'fund_ids', 'q', 'implementation_id',
            'with_external', 'has_products', 'has_subsidies', 'has_providers',
            'order_by', 'order_dir',
        ]), Implementation::queryFundsByState($state)))->query();

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
     * @noinspection PhpUnused
     */
    public function redeem(RedeemFundsRequest $request): JsonResponse
    {
        $vouchers = [];
        $prevalidation = $request->getPrevalidation();
        $vouchersAvailable = $request->getAvailableVouchers();

        if ($prevalidation && Gate::allows('redeem', $prevalidation)) {
            $prevalidation->assignToIdentity($request->identity());
            $vouchers = $request->implementation()->makeVouchersInApplicableFunds($request->identity());
        }

        // check permissions of all voucher before assigning
        foreach ($vouchersAvailable as $voucher) {
            if (Gate::allows('redeem', $voucher)) {
                $vouchers[] = $voucher->assignToIdentity($request->identity());
            }
        }

        return new JsonResponse([
            'prevalidation' => $prevalidation ? PrevalidationResource::create($prevalidation) : null,
            'vouchers' => VoucherResource::collection($vouchers),
        ]);
    }

    /**
     * Apply fund for identity
     *
     * @param BaseFormRequest $request
     * @param Fund $fund
     * @return VoucherResource|null
     * @throws \Illuminate\Auth\Access\AuthorizationException|Exception
     */
    public function apply(BaseFormRequest $request, Fund $fund): ?VoucherResource
    {
        $this->authorize('apply', [$fund, 'apply']);

        if ($error = $fund->getResolvingError()) {
            $fund->logError('[invalid_iban_record_keys]', ['action' => 'fund_apply']);
            throw new Exception(App::hasDebugModeEnabled() ? $error : 'Niet beschikbaar.', 403);
        }

        $voucher = $fund->makeVoucher($request->auth_address());
        $formulaProductVouchers = $fund->makeFundFormulaProductVouchers($request->auth_address());

        $voucher = $voucher ?: array_first($formulaProductVouchers) ?: $fund->vouchers()->where([
            'identity_address' => $request->auth_address(),
        ])->first();

        return $voucher ? new VoucherResource($voucher) : null;
    }

    /**
     * Apply fund for identity
     *
     * @param CheckFundRequest $request
     * @param Fund $fund
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function check(CheckFundRequest $request, Fund $fund): JsonResponse
    {
        $this->authorize('check', $fund);

        $vouchers = Voucher::assignAvailableToIdentityByBsn($request->identity());
        $prevalidations = Prevalidation::assignAvailableToIdentityByBsn($request->identity());
        $prevalidation_vouchers = $prevalidations > 0 ? VoucherResource::collection(
            $request->implementation()->makeVouchersInApplicableFunds($request->identity())
        ) : [];

        $hasBackoffice = $fund->fund_config && $fund->organization->backoffice_available;

        $backofficeResponse = $fund->checkBackofficeIfAvailable($request->identity());
        $backoffice = $hasBackoffice ? $fund->backofficeResponseToData($backofficeResponse) : null;

        return new JsonResponse(compact(
            'backoffice', 'vouchers', 'prevalidations', 'prevalidation_vouchers'
        ));
    }
}
