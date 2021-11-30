<?php

namespace App\Notifications\Identities\Fund;

use App\Models\Fund;
use App\Notifications\Identities\BaseIdentityNotification;
use App\Scopes\Builders\VoucherQuery;
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
        $vouchers = VoucherQuery::whereNotExpiredAndActive(
            $loggable->vouchers()->select('vouchers.*')->getQuery()
        )->select('vouchers.identity_address')->distinct();

        return Identity::whereIn('address', $vouchers)->get();
    }
}
