<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor\Vouchers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Vouchers\PhysicalCardRequests\StorePhysicalCardRequestRequest;
use App\Http\Resources\PhysicalCardRequestResource;
use App\Http\Resources\Sponsor\SponsorPhysicalCardRequestResource;
use App\Models\Organization;
use App\Models\VoucherToken;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PhysicalCardRequestsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Organization $organization
     * @param VoucherToken $voucherToken
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return AnonymousResourceCollection
     */
    public function index(
        Organization $organization,
        VoucherToken $voucherToken,
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('showSponsor', [$voucherToken->voucher, $organization]);

        return SponsorPhysicalCardRequestResource::queryCollection(
            $voucherToken->voucher->physical_card_requests()->orderByDesc('created_at')
        );
    }

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
            'first_name', 'last_name',
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
