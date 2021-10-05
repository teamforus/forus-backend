<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

/**
 * Class ProviderRejectedMail
 * @package App\Mail\Funds
 */
class ProviderRejectedMail extends ImplementationMail
{
    protected $notificationTemplateKey = 'notifications_fund_providers.revoked_budget';

    /**
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildNotificationTemplatedMail();
    }
}
