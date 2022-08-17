<?php

namespace App\Notifications\Organizations\Funds;

use App\Mail\Funds\FundBalanceWarningMail;
use App\Models\Fund;
use App\Models\Identity;

/**
 * Notify sponsor that the fund balance is low (reached the threshold)
 */
class BalanceLowNotification extends BaseFundsNotification
{
    protected static ?string $key = 'notifications_funds.balance_low';
    protected static string|array $permissions = 'view_finances';

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var Fund $fund */
        $fund = $this->eventLog->loggable;
        $mailable = new FundBalanceWarningMail($this->eventLog->data, $fund->getEmailFrom());

        $this->sendMailNotification($identity->email, $mailable);
    }
}
