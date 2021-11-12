<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor\Vouchers;

use App\Events\Vouchers\VoucherPhysicalCardRequestedEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Vouchers\PhysicalCardRequests\StorePhysicalCardRequestRequest;
use App\Http\Resources\PhysicalCardRequestResource;
use App\Models\Organization;
use App\Models\VoucherToken;

/**
 * Class PhysicalCardRequestsController
 * @package App\Http\Controllers\Api\Platform\Vouchers
 */
class PhysicalCardRequestsController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param StorePhysicalCardRequestRequest $request
     * @param Organization $organization
     * @param VoucherToken $voucherToken
     * @return PhysicalCardRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
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
            'address', 'house', 'house_addition', 'postcode', 'city',
        ]), [
            'employee_id' => $organization->findEmployee($request->auth_address())->id,
        ]));

        VoucherPhysicalCardRequestedEvent::broadcast($voucherToken->voucher, $cardRequest);

        return new PhysicalCardRequestResource($cardRequest);
    }

    /**
     * Validate store a physical card
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
    ): void {}
}
