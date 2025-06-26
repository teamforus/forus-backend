<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Funds\CheckFundRequest;
use App\Http\Requests\Api\Platform\Funds\IndexFundsRequest;
use App\Http\Requests\Api\Platform\Funds\RedeemFundsRequest;
use App\Http\Requests\Api\Platform\Funds\ViewFundRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\FundResource;
use App\Http\Resources\PrevalidationResource;
use App\Http\Resources\VoucherResource;
use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Prevalidation;
use App\Models\Voucher;
use App\Searches\FundSearch;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;

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
        $query = (new FundSearch($request->only([
            'tag', 'tag_id', 'organization_id', 'fund_id', 'fund_ids', 'q', 'implementation_id',
            'with_external', 'has_products', 'has_providers', 'order_by', 'order_dir',
        ]), Implementation::queryFundsByState(Fund::STATE_ACTIVE)))->query();

        $organizations = Organization::whereIn('id', (clone $query)->select('organization_id'))->get();
        $organizations = $organizations->map(fn (Organization $item) => $item->only('id', 'name'));
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
     * @param ViewFundRequest $request
     * @param Fund $fund
     * @return FundResource|null
     */
    public function show(ViewFundRequest $request, Fund $fund): ?FundResource
    {
        return $request->authorize() ? FundResource::create($fund) : null;
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
     * Apply fund for identity.
     *
     * @param BaseFormRequest $request
     * @param Fund $fund
     * @throws \Illuminate\Auth\Access\AuthorizationException|Exception
     * @return VoucherResource|null
     */
    public function apply(BaseFormRequest $request, Fund $fund): ?VoucherResource
    {
        $this->authorize('apply', [$fund, 'apply']);

        if ($error = $fund->getResolvingError()) {
            $fund->logError('[invalid_iban_record_keys]', ['action' => 'fund_apply']);
            throw new Exception(App::hasDebugModeEnabled() ? $error : 'Niet beschikbaar.', 403);
        }

        $voucher = $fund->makeVoucher($request->identity());
        $formulaProductVouchers = $fund->makeFundFormulaProductVouchers($request->identity());

        $voucher = $voucher ?: array_first($formulaProductVouchers) ?: $fund->vouchers()->where([
            'identity_id' => $request->auth_id(),
        ])->first();

        return $voucher ? new VoucherResource($voucher) : null;
    }

    /**
     * Apply fund for identity.
     *
     * @param CheckFundRequest $request
     * @param Fund $fund
     * @throws AuthorizationException
     * @return JsonResponse
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
            'backoffice',
            'vouchers',
            'prevalidations',
            'prevalidation_vouchers'
        ));
    }
}
