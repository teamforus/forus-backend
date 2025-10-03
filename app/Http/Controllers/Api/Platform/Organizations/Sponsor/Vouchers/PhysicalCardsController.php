<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor\Vouchers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Vouchers\PhysicalCards\StorePhysicalCardRequest;
use App\Http\Resources\Sponsor\SponsorPhysicalCardResource;
use App\Models\Organization;
use App\Models\PhysicalCard;
use App\Models\Voucher;
use Illuminate\Http\JsonResponse;

class PhysicalCardsController extends Controller
{
    /**
     * Link existing physical card to existing voucher.
     * @param StorePhysicalCardRequest $request
     * @param Organization $organization
     * @param Voucher $voucher
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return SponsorPhysicalCardResource
     */
    public function store(
        StorePhysicalCardRequest $request,
        Organization $organization,
        Voucher $voucher
    ): SponsorPhysicalCardResource {
        $fundPhysicalCardType = $voucher->fund
            ->fund_physical_card_types()
            ->findOrFail($request->post('fund_physical_card_type_id'));

        $this->authorize('show', $organization);
        $this->authorize('showSponsor', [$voucher, $organization]);
        $this->authorize('createSponsor', [PhysicalCard::class, $fundPhysicalCardType, $voucher, $organization]);

        return new SponsorPhysicalCardResource($voucher->addPhysicalCard(
            $request->post('code'),
            $fundPhysicalCardType->physical_card_type,
        ));
    }

    /**
     * Unlink physical card from voucher.
     *
     * @param Organization $organization
     * @param Voucher $voucher
     * @param PhysicalCard $physicalCard
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return JsonResponse
     */
    public function destroy(
        Organization $organization,
        Voucher $voucher,
        PhysicalCard $physicalCard
    ): JsonResponse {
        $this->authorize('show', $organization);
        $this->authorize('showSponsor', [$voucher, $organization]);
        $this->authorize('deleteSponsor', [$physicalCard, $voucher, $organization]);

        $voucher->physical_cards()->where([
            'physical_cards.id' => $physicalCard->id,
        ])->delete();

        return new JsonResponse([]);
    }
}
