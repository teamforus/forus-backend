<?php

namespace App\Http\Controllers\Api\Platform\Vouchers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Vouchers\PhysicalCards\StorePhysicalCardRequest;
use App\Http\Resources\PhysicalCardResource;
use App\Models\PhysicalCard;
use App\Models\Voucher;
use App\Traits\ThrottleWithMeta;
use Illuminate\Http\Response;

class PhysicalCardsController extends Controller
{
    use ThrottleWithMeta;

    private $maxAttempts = 5;
    private $decayMinutes = 60 * 24;

    /**
     * Link existing physical card to existing voucher.
     * @param StorePhysicalCardRequest $request
     * @param Voucher $voucher
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \App\Exceptions\AuthorizationJsonException
     * @return PhysicalCardResource
     */
    public function store(StorePhysicalCardRequest $request, Voucher $voucher): PhysicalCardResource
    {
        $fundPhysicalCardType = $voucher->fund
            ->fund_physical_card_types()
            ->findOrFail($request->post('fund_physical_card_type_id'));

        $this->throttleWithKey('to_many_attempts', $request, 'physical_cards');
        $this->authorize('create', [PhysicalCard::class, $fundPhysicalCardType, $voucher]);

        return PhysicalCardResource::create($voucher->addPhysicalCard(
            $request->post('code'),
            $fundPhysicalCardType->physical_card_type,
        ));
    }

    /**
     * Unlink physical card from voucher.
     *
     * @param Voucher $voucher
     * @param PhysicalCard $physicalCard
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return Response
     */
    public function destroy(Voucher $voucher, PhysicalCard $physicalCard): Response
    {
        $this->authorize('show', $voucher);
        $this->authorize('delete', [$physicalCard, $voucher]);

        $voucher->physical_cards()->where([
            'physical_cards.id' => $physicalCard->id,
        ])->delete();

        return new Response('', 200);
    }
}
