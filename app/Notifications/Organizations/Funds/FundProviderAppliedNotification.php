<?php

namespace App\Notifications\Organizations\Funds;

use App\Mail\Funds\ProviderAppliedMail;
use App\Models\Fund;
use App\Services\Forus\Identity\Models\Identity;

/**
 * Class FundProviderAppliedNotification
 * @package App\Notifications\Organizations\Funds
 */
class FundProviderAppliedNotification extends BaseFundsNotification {
    protected $key = 'notifications_funds.provider_applied';
    protected $sendMail = true;

    protected static $permissions = [
        'manage_providers',
    ];

    /**
     * Get the mail representation of the notification.
     *
     * @param Identity $identity
     * @return void
     */
    public function toMail(Identity $identity) {
        /** @var Fund $fund */
        $fund = $this->eventLog->loggable;

        resolve('forus.services.notification')->sendMailNotification(
            $identity->primary_email->email,
            new ProviderAppliedMail(
                $this->eventLog->data['provider_name'],
                $this->eventLog->data['sponsor_name'],
                $this->eventLog->data['fund_name'],
                $fund->urlSponsorDashboard(),
                $fund->fund_config->implementation->getEmailFrom()
            )
        );
    }
}
