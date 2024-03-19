<?php

namespace App\Notifications\Identities\FundRequest;

use App\Models\FundRequestRecord;
use App\Models\Identity;
use App\Notifications\Identities\BaseIdentityNotification;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Support\Collection;

abstract class BaseIdentityFundRequestRecordNotification extends BaseIdentityNotification
{
    /**
     * Get identities which are eligible for the notification
     *
     * @param FundRequestRecord $loggable
     * @param EventLog $eventLog
     *
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     *
     * @psalm-return \Illuminate\Database\Eloquent\Collection<array-key, \Illuminate\Database\Eloquent\Model>|array<\Illuminate\Database\Eloquent\Builder>
     */
    public static function eligibleIdentities($loggable, EventLog $eventLog): array|\Illuminate\Database\Eloquent\Collection
    {
        return Identity::whereAddress($loggable->fund_request->identity)->get();
    }
}
