<?php

namespace App\Notifications\Identities\FundRequest;

use App\Models\FundRequestRecord;
use App\Models\Identity;
use App\Notifications\Identities\BaseIdentityNotification;
use Illuminate\Support\Collection;

abstract class BaseIdentityFundRequestRecordNotification extends BaseIdentityNotification
{
    /**
     * Get identities which are eligible for the notification
     *
     * @param FundRequestRecord $loggable
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public static function eligibleIdentities($loggable): Collection
    {
        return Identity::whereAddress($loggable->fund_request->identity_address)->get();
    }
}