<?php

namespace App\Notifications\Identities\FundRequest;

use App\Models\FundRequest;
use App\Notifications\Identities\BaseIdentityNotification;
use App\Services\Forus\Identity\Models\Identity;
use Illuminate\Support\Collection;

abstract class BaseIdentityFundRequestNotification extends BaseIdentityNotification
{
    /**
     * Get identities which are eligible for the notification
     *
     * @param FundRequest $loggable
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public static function eligibleIdentities($loggable): Collection
    {
        return Identity::whereAddress($loggable->identity_address)->get();
    }
}
