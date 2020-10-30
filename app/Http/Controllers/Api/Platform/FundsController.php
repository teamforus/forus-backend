<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Requests\Api\Platform\Funds\IndexFundsRequest;
use App\Http\Requests\Api\Platform\Funds\StoreIdealBunqMeRequestRequest;
use App\Http\Resources\BunqIdealIssuerResource;
use App\Http\Resources\BunqMeIdealRequestResource;
use App\Http\Resources\FundResource;
use App\Http\Resources\VoucherResource;
use App\Models\Fund;
use App\Http\Controllers\Controller;
use App\Models\Implementation;
use App\Models\Voucher;
use App\Services\BunqService\BunqService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;

/**
 * Class FundsController
 * @package App\Http\Controllers\Api\Platform
 */
class FundsController extends Controller
{
    /**
     * Display a listing of all active funds.
     *
     * @param IndexFundsRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(
        IndexFundsRequest $request
    ): AnonymousResourceCollection {
        $state = $request->input('state') === 'active_and_closed' ? [
            Fund::STATE_CLOSED,
            Fund::STATE_ACTIVE,
        ] : Fund::STATE_ACTIVE;

        $query = Fund::search($request, Implementation::queryFundsByState($state));
        $meta = [
            'organizations' => $query->with('organization')->get()->pluck(
                'organization.name', 'organization.id'
            )->map(static function ($name, $id) {
                return (object) compact('id', 'name');
            })->toArray()
        ];

        if ($per_page = $request->input('per_page', false)) {
            return FundResource::collection(
                $query->paginate($per_page)
            )->additional(compact('meta'));
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
     * Apply fund for identity
     *
     * @param Fund $fund
     * @return VoucherResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function apply(
        Fund $fund
    ): VoucherResource {
        $this->authorize('apply', $fund);

        $identity_address = auth_address();
        $voucher = $fund->makeVoucher($identity_address);

        return new VoucherResource($voucher ?: $fund->vouchers()->where([
            'identity_address' => $identity_address,
        ])->first());
    }

    // TODO: remove bunq ideal requests
    /**
     * @param Fund $fund
     * @return AnonymousResourceCollection
     */
    public function idealIssuers(Fund $fund): AnonymousResourceCollection {
        $allowIssuers = env('BUNQ_IDEAL_USE_ISSUERS', true);

        return BunqIdealIssuerResource::collection(
            $allowIssuers ? BunqService::getIdealIssuers(
                $fund->fund_config->bunq_sandbox
            ) : collect()
        );
    }

    /**
     * @param StoreIdealBunqMeRequestRequest $request
     * @param Fund $fund
     * @return BunqMeIdealRequestResource
     * @throws \Exception
     */
    public function idealMakeRequest(
        StoreIdealBunqMeRequestRequest $request,
        Fund $fund
    ): BunqMeIdealRequestResource {
        $this->authorize('idealRequest', $fund);

        return new BunqMeIdealRequestResource($fund->makeBunqMeTab(
            $request->input('amount'),
            (string) $request->input('description'),
            $request->input('issuer', false)
        ));
    }
}
