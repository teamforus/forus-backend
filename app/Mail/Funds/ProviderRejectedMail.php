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
    // protected $subjectKey = 'mails/provider_rejected.title';
    // protected $viewKey = 'emails.funds.provider_rejected';

    protected $notificationTemplateKey = 'notifications_fund_providers.revoked_budget';

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): Mailable
    {
        return $this->buildTemplatedNotification();
    }
}
