<?php

namespace App\Notifications\Identities\Fund;

use App\Models\Fund;
use App\Notifications\Identities\BaseIdentityNotification;
use App\Services\Forus\Identity\Models\Identity;
use Illuminate\Support\Collection;

abstract class BaseIdentityFundNotification extends BaseIdentityNotification
{
    /**
     * Get identities which are eligible for the notification
     *
     * @param Fund $loggable
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public static function eligibleIdentities($loggable): Collection
    {
        $identities = $loggable->vouchers()->pluck('identity_address')->unique();

        log_debug($identities);
        log_debug($loggable->vouchers()->toSql());
        log_debug($loggable->vouchers->toJson(JSON_PRETTY_PRINT));

        return Identity::whereIn('address', $identities->toArray())->get();
    }
}
