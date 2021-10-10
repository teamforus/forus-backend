<?php

namespace App\Notifications\Identities\Fund;

use App\Mail\Funds\FundClosed;

/**
 * Notify requester that the fund has ended
 */
class IdentityRequesterFundEndedNotification extends BaseIdentityFundNotification
{
    protected static $key = 'notifications_identities.requester_fund_ended';
    protected static $scope = null;
}
