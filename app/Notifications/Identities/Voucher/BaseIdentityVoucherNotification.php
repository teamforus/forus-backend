<?php

namespace App\Notifications\Identities\Voucher;

use App\Models\Voucher;
use App\Notifications\Identities\BaseIdentityNotification;
use App\Services\Forus\Identity\Models\Identity;
use Illuminate\Support\Collection;

abstract class BaseIdentityVoucherNotification extends BaseIdentityNotification
{
    /**
     * Get identities which are eligible for the notification
     *
     * @param Voucher $loggable
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public static function eligibleIdentities($loggable): Collection
    {
        return Identity::whereAddress($loggable->identity_address)->get();
    }
}
