<?php

namespace App\Http\Controllers\Api\Platform\Vouchers;

use App\Http\Controllers\Controller;
use App\Http\Resources\PhysicalCardResource;
use App\Models\PhysicalCard;
use App\Models\VoucherToken;
use App\Http\Requests\Api\Platform\Vouchers\PhysicalCards\StorePhysicalCardRequest;
use Illuminate\Http\Response;

/**
 * Class PhysicalCardsController
 * @package App\Http\Controllers\Api\Platform\Vouchers
 */
class PhysicalCardsController extends Controller
{
    private $maxAttempts = 5;
    private $decayMinutes = 60 * 24;

    /**
     * PhysicalCardRequestsController constructor.
     */
    public function __construct()
    {
        $this->middleware(sprintf(
            'throttle:%s,%s,physical_cards',
            $this->maxAttempts,
            $this->decayMinutes
        ))->only('store');
    }

    /**
     * Link existing physical card to existing voucher
     * @param StorePhysicalCardRequest $request
     * @param VoucherToken $voucherToken
     * @return PhysicalCardResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StorePhysicalCardRequest $request,
        VoucherToken $voucherToken
    ): PhysicalCardResource {
        $this->authorize('storePhysicalCard', $voucherToken->voucher);

        return new PhysicalCardResource($voucherToken->voucher->physical_cards()->create(
            $request->only('code')
        ));
    }

    /**
     * Unlink physical card from voucher
     *
     * @param VoucherToken $voucherToken
     * @param PhysicalCard $physicalCard
     * @return Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(
        VoucherToken $voucherToken,
        PhysicalCard $physicalCard
    ): Response {
        $this->authorize('show', $voucherToken->voucher);

        $voucherToken->voucher->physical_cards()->where([
            'physical_cards.id' => $physicalCard->id
        ])->delete();

        return new Response('', 200);
    }
}
