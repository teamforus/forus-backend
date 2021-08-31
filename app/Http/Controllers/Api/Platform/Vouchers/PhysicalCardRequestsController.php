<?php

namespace App\Http\Controllers\Api\Platform\Vouchers;

use App\Events\PhysicalCardRequests\PhysicalCardRequestsCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Vouchers\PhysicalCardRequests\StorePhysicalCardRequestRequest;
use App\Http\Resources\PhysicalCardRequestResource;
use App\Models\PhysicalCardRequest;
use App\Models\VoucherToken;
use App\Traits\ThrottleWithMeta;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Services\Forus\Notification\NotificationService;
use App\Services\Forus\Identity\Repositories\IdentityRepo;

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
     * Display a listing of the resource.
     *
     * @param VoucherToken $voucherToken
     * @return AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        VoucherToken $voucherToken
    ): AnonymousResourceCollection {
        $voucher = $voucherToken->voucher;

        $this->authorize('show', $voucher);
        $this->authorize('showAny', [PhysicalCardRequest::class, $voucher]);

        return PhysicalCardRequestResource::collection(
            $voucher->physical_card_requests()->orderByDesc('created_at')->paginate()
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StorePhysicalCardRequestRequest $request
     * @param VoucherToken $voucherToken
     * @return PhysicalCardRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \App\Exceptions\AuthorizationJsonException
     */
    public function store(
        StorePhysicalCardRequestRequest $request,
        VoucherToken $voucherToken
    ): PhysicalCardRequestResource {
        $this->authorize('requestPhysicalCard', $voucherToken->voucher);
        $this->throttleWithKey('to_many_attempts', $request, 'physical_card_requests');

        $fund = $voucherToken->voucher->fund;

        $this->mailService->requestPhysicalCard(
            $this->identityRepo->getPrimaryEmail($request->auth_address()),
            $fund->getEmailFrom(), [
                'postcode'       => $request->input('postcode'),
                'house_number'   => $request->input('house'),
                'house_addition' => $request->input('house_addition'),
                'city'           => $request->input('city'),
                'street_name'    => $request->input('address'),
                'fund_name'      => $fund->name,
                'sponsor_phone'  => $fund->organization->phone,
                'sponsor_email'  => $fund->organization->email
            ]);

        $cardRequest = $voucherToken->voucher->physical_card_requests()->create($request->only(
            'address', 'house', 'house_addition', 'postcode', 'city'
        ));

        PhysicalCardRequestsCreated::dispatch($cardRequest);

        return new PhysicalCardRequestResource($cardRequest);
    }

    /**
     * Validate store a physical card
     *
     * @param StorePhysicalCardRequestRequest $request
     * @param VoucherToken $voucherToken
     * @noinspection PhpUnused
     */
    public function storeValidate(
        StorePhysicalCardRequestRequest $request,
        VoucherToken $voucherToken
    ): void {}

    /**
     * Display the specified resource.
     *
     * @param VoucherToken $voucherToken
     * @param PhysicalCardRequest $physicalCardRequest
     * @return PhysicalCardRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        VoucherToken $voucherToken,
        PhysicalCardRequest  $physicalCardRequest
    ): PhysicalCardRequestResource {
        $this->authorize('show', $voucherToken->voucher);
        $this->authorize('show', $physicalCardRequest);

        return new PhysicalCardRequestResource($physicalCardRequest);
    }
}
