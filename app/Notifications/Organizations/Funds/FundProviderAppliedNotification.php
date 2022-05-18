<?php

namespace App\Notifications\Organizations\Funds;

use App\Mail\Funds\ProviderAppliedMail;
use App\Models\Fund;
use App\Services\Forus\Identity\Models\Identity;

/**
 * Notify sponsor that a new provider applied to the fund
 */
class FundProviderAppliedNotification extends BaseFundsNotification
{
    protected static ?string $key = 'notifications_funds.provider_applied';
    protected static $permissions = 'manage_providers';

    /**
     * Get the mail representation of the notification.
     *
     * @param Identity $identity
     * @return void
     */
    public function toMail(Identity $identity): void {
        /** @var Fund $fund */
        $fund = $this->eventLog->loggable;

        $mailable = new ProviderAppliedMail(array_merge($this->eventLog->data, [
            'sponsor_dashboard_link' => $fund->urlSponsorDashboard(),
        ]), $fund->fund_config->implementation->getEmailFrom());

        $this->sendMailNotification($identity->primary_email->email, $mailable);
    }
}
