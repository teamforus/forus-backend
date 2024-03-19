<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class ProviderRejectedMail extends ImplementationMail
{
    protected string $notificationTemplateKey = 'notifications_fund_providers.revoked_budget';

    /**
     * @throws CommonMarkException
     */
    public function build(): Mailable|null
    {
        return $this->buildNotificationTemplatedMail();
    }
}
