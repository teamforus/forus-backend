<?php

namespace App\Notifications\Identities\FundRequest;

use App\Models\FundRequest;
use App\Notifications\Identities\BaseIdentityNotification;
use App\Models\Identity;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Support\Collection;

abstract class BaseIdentityFundRequestNotification extends BaseIdentityNotification
{
    /**
     * Get identities which are eligible for the notification
     *
     * @param FundRequest $loggable
     * @param EventLog $eventLog
     * @return \Illuminate\Support\Collection
     */
    public static function eligibleIdentities($loggable, EventLog $eventLog): Collection
    {
        return Identity::whereAddress($loggable->identity_address)->get();
    }
}
