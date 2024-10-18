<?php

namespace App\Http\Controllers\Api\Platform\Vouchers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Vouchers\PhysicalCardRequests\StorePhysicalCardRequestRequest;
use App\Http\Resources\PhysicalCardRequestResource;
use App\Models\PhysicalCardRequest;
use App\Models\Voucher;
use App\Traits\ThrottleWithMeta;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Class PhysicalCardRequestsController
 * @package App\Http\Controllers\Api\Platform\Vouchers
 */
class PhysicalCardRequestsController extends Controller
{
    use ThrottleWithMeta;

    private $maxAttempts = 3;
    private $decayMinutes = 60 * 24;

    /**
     * Display a listing of the resource.
     *
     * @param Voucher $voucher
     * @return AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Voucher $voucher): AnonymousResourceCollection
    {
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
     * @param Voucher $voucher
     * @return PhysicalCardRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \App\Exceptions\AuthorizationJsonException
     */
    public function store(
        StorePhysicalCardRequestRequest $request,
        Voucher $voucher,
    ): PhysicalCardRequestResource {
        $this->authorize('requestPhysicalCard', $voucher);
        $this->throttleWithKey('to_many_attempts', $request, 'physical_card_requests');

        $cardRequest = $voucher->makePhysicalCardRequest($request->only([
            'address', 'house', 'house_addition', 'postcode', 'city',
        ]), true);

        return new PhysicalCardRequestResource($cardRequest);
    }

    /**
     * Validate store a physical card
     *
     * @param StorePhysicalCardRequestRequest $request
     * @param Voucher $voucher
     * @noinspection PhpUnused
     */
    public function storeValidate(
        StorePhysicalCardRequestRequest $request,
        Voucher $voucher,
    ): void {}

    /**
     * Display the specified resource.
     *
     * @param Voucher $voucher
     * @param PhysicalCardRequest $physicalCardRequest
     * @return PhysicalCardRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Voucher $voucher,
        PhysicalCardRequest  $physicalCardRequest
    ): PhysicalCardRequestResource {
        $this->authorize('show', $voucher);
        $this->authorize('show', $physicalCardRequest);

        return new PhysicalCardRequestResource($physicalCardRequest);
    }
}
