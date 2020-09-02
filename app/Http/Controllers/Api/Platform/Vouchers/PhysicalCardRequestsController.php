<?php

namespace App\Http\Controllers\Api\Platform\Vouchers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Vouchers\PhysicalCards\StorePhysicalCardRequestRequest;
use App\Http\Resources\PhysicalCardRequestResource;
use App\Models\PhysicalCardRequest;
use App\Models\VoucherToken;
use App\Traits\ThrottleWithMeta;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PhysicalCardRequestsController extends Controller
{
    use ThrottleWithMeta;

    private $maxAttempts = 3;
    private $decayMinutes = 60 * 24;

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

        if ($this->hasTooManyLoginAttempts($request)) {
            $this->responseWithThrottleMeta(
                'to_many_attempts', $request, 'physical_card_requests'
            );
        }

        $this->incrementLoginAttempts($request);

        $cardRequest = $voucherToken->voucher->physical_card_requests()->create($request->only(
            'address', 'house', 'house_addition', 'postcode', 'city'
        ));

        return new PhysicalCardRequestResource($cardRequest);
    }

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



    /**
     * Get the throttle key for the given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function throttleKey(Request $request): string
    {
        return strtolower(sprintf('physical_card_requests_%s', $request->ip()));
    }
}
