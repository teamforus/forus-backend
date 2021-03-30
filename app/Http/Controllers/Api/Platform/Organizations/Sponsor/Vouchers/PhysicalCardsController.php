<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor\Vouchers;

use App\Http\Controllers\Controller;
use App\Http\Resources\PhysicalCardResource;
use App\Models\Organization;
use App\Models\PhysicalCard;
use App\Models\Voucher;
use App\Models\VoucherToken;
use App\Http\Requests\Api\Platform\Vouchers\PhysicalCards\StorePhysicalCardRequest;
use App\Traits\ThrottleWithMeta;
use Illuminate\Http\Response;

/**
 * Class PhysicalCardsController
 * @package App\Http\Controllers\Api\Platform\Vouchers
 */
class PhysicalCardsController extends Controller
{
    use ThrottleWithMeta;

    private $maxAttempts = 5;
    private $decayMinutes = 60 * 24;

    /**
     * Link existing physical card to existing voucher
     * @param StorePhysicalCardRequest $request
     * @param Organization $organization
     * @param Voucher $voucher
     * @return PhysicalCardResource
     * @throws \App\Exceptions\AuthorizationJsonException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StorePhysicalCardRequest $request,
        Organization $organization,
        Voucher $voucher
    ): PhysicalCardResource {
//        $this->throttleWithKey('to_many_attempts', $request, 'physical_cards');
        $this->authorize('show', $organization);
        $this->authorize('showSponsor', [$voucher, $organization]);
        $this->authorize('storePhysicalCardSponsor', [$voucher, $organization]);

        return new PhysicalCardResource($voucher->physical_cards()->create(
            $request->only('code')
        ));
    }

    /**
     * Unlink physical card from voucher
     *
     * @param Organization $organization
     * @param Voucher $voucher
     * @param PhysicalCard $physicalCard
     * @return Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(
        Organization $organization,
        Voucher $voucher,
        PhysicalCard $physicalCard
    ): Response {
        $this->authorize('show', $organization);
        $this->authorize('showSponsor', [$voucher, $organization]);

        $voucher->physical_cards()->where([
            'physical_cards.id' => $physicalCard->id
        ])->delete();

        return new Response('', 200);
    }
}
