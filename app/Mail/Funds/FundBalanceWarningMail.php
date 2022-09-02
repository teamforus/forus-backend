<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

/**
 * Class BalanceWarningMail
 * @package App\Mail\Funds
 */
class FundBalanceWarningMail extends ImplementationMail
{
    protected string $notificationTemplateKey = "notifications_funds.balance_low";

    /**
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildNotificationTemplatedMail();
    }
}
