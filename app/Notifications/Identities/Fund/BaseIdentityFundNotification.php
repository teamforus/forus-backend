<?php

namespace App\Notifications\Identities\Fund;

use App\Models\Fund;
use App\Notifications\Identities\BaseIdentityNotification;
use App\Scopes\Builders\VoucherQuery;
use App\Models\Identity;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Support\Collection;

abstract class BaseIdentityFundNotification extends BaseIdentityNotification
{
    /**
     * Get identities which are eligible for the notification
     *
     * @param Fund $loggable
     * @param EventLog $eventLog
     * @return \Illuminate\Support\Collection
     */
    public static function eligibleIdentities($loggable, EventLog $eventLog): Collection
    {
        $vouchers = VoucherQuery::whereNotExpiredAndActive(
            $loggable->vouchers()->select('vouchers.*')->getQuery()
        )->select('vouchers.identity_address')->distinct();

        return Identity::whereIn('address', $vouchers)->get();
    }
}
