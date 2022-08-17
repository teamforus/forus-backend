<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Voucher;
use App\Models\PhysicalCardRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class PhysicalCardRequestPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any physical card requests.
     *
     * @param Identity $identity
     * @param Voucher $voucher
     * @return bool
     * @noinspection PhpUnused
     */
    public function showAny(Identity $identity, Voucher $voucher): bool
    {
        return $voucher->identity_address === $identity->address;
    }

    /**
     * Determine whether the user can view the physical card request.
     *
     * @param Identity $identity
     * @param Voucher $voucher
     * @param \App\Models\PhysicalCardRequest $physicalCardRequest
     * @return bool
     * @noinspection PhpUnused
     */
    public function show(
        Identity $identity,
        Voucher $voucher,
        PhysicalCardRequest $physicalCardRequest
    ): bool {
        return
            $voucher->identity_address === $identity->address &&
            $physicalCardRequest->voucher_id === $voucher->id;
    }
}
