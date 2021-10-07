<?php

namespace App\Policies;

use App\Models\Voucher;
use App\Models\PhysicalCardRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class PhysicalCardRequestPolicy
 * @package App\Policies
 */
class PhysicalCardRequestPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any physical card requests.
     *
     * @param string $identity_address
     * @param Voucher $voucher
     * @return mixed
     */
    public function showAny(string $identity_address, Voucher $voucher): bool
    {
        return $voucher->identity_address === $identity_address;
    }

    /**
     * Determine whether the user can view the physical card request.
     *
     * @param string $identity_address
     * @param Voucher $voucher
     * @param \App\Models\PhysicalCardRequest $physicalCardRequest
     * @return mixed
     */
    public function show(
        string $identity_address,
        Voucher $voucher,
        PhysicalCardRequest $physicalCardRequest
    ): bool {
        return ($voucher->identity_address === $identity_address) &&
            ($physicalCardRequest->voucher_id === $voucher->id);
    }
}
