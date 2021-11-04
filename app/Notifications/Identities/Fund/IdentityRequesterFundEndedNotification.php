<?php

namespace App\Notifications\Identities\Fund;

use App\Mail\Funds\FundClosed;
use App\Models\Fund;
use App\Scopes\Builders\VoucherQuery;
use App\Services\Forus\Identity\Models\Identity;
use Illuminate\Support\Collection;

/**
 * Notify requester that the fund has ended
 */
class IdentityRequesterFundEndedNotification extends BaseIdentityFundNotification
{
    protected static $key = 'notifications_identities.requester_fund_ended';
    protected static $scope = null;
    /**
     * Get identities which are eligible for the notification
     *
     * @param Fund $loggable
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public static function eligibleIdentities($loggable): Collection
    {
        $vouchers = VoucherQuery::whereActive(
            $loggable->vouchers()->select('vouchers.*')->getQuery()
        )->select('vouchers.identity_address')->distinct();

        return Identity::whereIn('address', $vouchers)->get();
    }
}
