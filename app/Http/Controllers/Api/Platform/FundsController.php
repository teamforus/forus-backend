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
use App\Services\BunqService\BunqService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
        $query = Fund::search(
            $request,
            $request->input('state') === 'active_and_closed' ?
                Implementation::queryFundsByState([
                    Fund::STATE_CLOSED,
                    Fund::STATE_ACTIVE,
                ]) :
                Implementation::activeFundsQuery()
        );

        $meta = [
            'organizations' => Fund::with('organization')->get()->pluck(
                'organization.name', 'organization.id'
            )->map(static function ($name, $id) {
                return (object) [
                    'id'   => $id,
                    'name' => $name
                ];
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
     */
    public function apply(
        Fund $fund
    ): VoucherResource {
        $this->authorize('apply', $fund);

        return new VoucherResource($fund->makeVoucher(auth_address()));
    }

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
