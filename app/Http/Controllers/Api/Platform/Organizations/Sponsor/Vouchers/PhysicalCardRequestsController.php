<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor\Vouchers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Vouchers\PhysicalCardRequests\StorePhysicalCardRequestRequest;
use App\Http\Resources\PhysicalCardRequestResource;
use App\Models\Organization;
use App\Models\VoucherToken;

class PhysicalCardRequestsController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param StorePhysicalCardRequestRequest $request
     * @param Organization $organization
     * @param VoucherToken $voucherToken
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return PhysicalCardRequestResource
     */
    public function store(
        StorePhysicalCardRequestRequest $request,
        Organization $organization,
        VoucherToken $voucherToken
    ): PhysicalCardRequestResource {
        $this->authorize('show', $organization);
        $this->authorize('showSponsor', [$voucherToken->voucher, $organization]);
        $this->authorize('requestPhysicalCardAsSponsor', [$voucherToken->voucher, $organization]);

        $cardRequest = $voucherToken->voucher->makePhysicalCardRequest(array_merge($request->only([
            'address', 'house', 'house_addition', 'postcode', 'city', 'physical_card_type_id',
        ]), [
            'physical_card_type_id' => $voucherToken->voucher->fund
                ->fund_physical_card_types()
                ->findOrFail($request->post('fund_physical_card_type_id'))
                ->physical_card_type_id,
            'employee_id' => $organization->findEmployee($request->auth_address())->id,
        ]));

        return PhysicalCardRequestResource::create($cardRequest);
    }

    /**
     * Validate store a physical card.
     *
     * @param StorePhysicalCardRequestRequest $request
     * @param Organization $organization
     * @param VoucherToken $voucherToken
     * @noinspection PhpUnused
     */
    public function storeValidate(
        StorePhysicalCardRequestRequest $request,
        Organization $organization,
        VoucherToken $voucherToken
    ): void {
    }
}
