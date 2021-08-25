<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor\Vouchers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Vouchers\PhysicalCardRequests\StorePhysicalCardRequestRequest;
use App\Http\Resources\PhysicalCardRequestResource;
use App\Models\Organization;
use App\Models\VoucherToken;
use App\Services\Forus\Identity\Repositories\IdentityRepo;
use App\Services\Forus\Notification\NotificationService;
use App\Traits\ThrottleWithMeta;

/**
 * Class PhysicalCardRequestsController
 * @property IdentityRepo $identityRepo
 * @property NotificationService $mailService
 * @package App\Http\Controllers\Api\Platform\Vouchers
 */
class PhysicalCardRequestsController extends Controller
{
    use ThrottleWithMeta;

    private $maxAttempts = 3;
    private $decayMinutes = 60 * 24;

    private $mailService;
    private $recordService;

    /**
     * PhysicalCardRequestsController constructor.
     * @param IdentityRepo $identityRepo
     * @param NotificationService $mailService
     */
    public function __construct(
        IdentityRepo $identityRepo,
        NotificationService $mailService
    ) {
        $this->mailService   = $mailService;
        $this->identityRepo  = $identityRepo;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StorePhysicalCardRequestRequest $request
     * @param Organization $organization
     * @param VoucherToken $voucherToken
     * @return PhysicalCardRequestResource
     * @throws \App\Exceptions\AuthorizationJsonException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StorePhysicalCardRequestRequest $request,
        Organization $organization,
        VoucherToken $voucherToken
    ): PhysicalCardRequestResource {
        $this->authorize('requestPhysicalCard', $voucherToken->voucher);
        $this->authorize('requestPhysicalCardBySponsor', [$voucherToken->voucher, $organization]);

        $this->throttleWithKey('to_many_attempts', $request, 'physical_card_requests');

        $cardRequest = $voucherToken->voucher->storePhysicalCardRequest($request);

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
