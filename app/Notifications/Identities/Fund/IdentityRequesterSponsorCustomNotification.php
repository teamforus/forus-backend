<?php

namespace App\Notifications\Identities\Fund;

use App\Mail\Funds\FundSponsorCustomNotificationMail;
use App\Models\Fund;
use App\Models\Identity;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Support\Arr;

/**
 * Custom mailing from the sponsor
 */
class  IdentityRequesterSponsorCustomNotification extends BaseIdentityFundNotification
{
    protected static ?string $key = 'notifications_identities.requester_sponsor_custom_notification';

    /**
     * @param Identity $identity
     * @return void
     */
    public function toMail(Identity $identity): void
    {
        /** @var Fund $fund */
        $fund = $this->eventLog->loggable;
        $data = array_merge(array_filter($this->eventLog->data, fn($value) => is_string($value)), [
            'webshop_link' => $fund->urlWebshop(),
        ]);

        $this->sendMailNotification(
            $identity->email,
            new FundSponsorCustomNotificationMail($data, $fund->getEmailFrom())
        );
    }

    /**
     * Get identities which are eligible for the notification
     *
     * @param Fund $loggable
     * @param EventLog $eventLog
     * @return \Illuminate\Support\Collection
     */
    public static function eligibleIdentities($loggable, EventLog $eventLog): \Illuminate\Support\Collection
    {
        return Identity::query()
            ->whereIn('id', Arr::get($eventLog->data, 'notification_target_identities', []))
            ->take(env('SPONSOR_CUSTOM_NOTIFICATION_LIMIT', 1_000_000))
            ->get();
    }
}
