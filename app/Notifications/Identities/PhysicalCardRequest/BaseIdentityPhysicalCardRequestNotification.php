<?php

namespace App\Notifications\Identities\PhysicalCardRequest;

use App\Models\PhysicalCardRequest;
use App\Notifications\Identities\BaseIdentityNotification;
use App\Services\Forus\Identity\Models\Identity;
use Illuminate\Support\Collection;

/**
 * Class BaseIdentityPhysicalCardRequestNotification
 */
abstract class BaseIdentityPhysicalCardRequestNotification extends BaseIdentityNotification
{
    /**
     * Get identities which are eligible for the notification
     *
     * @param PhysicalCardRequest $loggable
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public static function eligibleIdentities($loggable): Collection
    {
        return Identity::whereAddress($loggable->voucher->identity_address)->get();
    }
}