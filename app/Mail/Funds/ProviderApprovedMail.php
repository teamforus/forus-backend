<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

/**
 * Class ProviderApprovedMail
 * @package App\Mail\Funds
 */
class ProviderApprovedMail extends ImplementationMail
{
    protected $notificationTemplateKey = 'notifications_fund_providers.approved_budget';

    /**
     * @return Mailable
     */
    public function build(): Mailable
    {
        $provider_dashboard_link = $this->mailData['provider_dashboard_link'];

        return $this->buildTemplatedNotification([
            'provider_dashboard_link' => $this->makeLink($provider_dashboard_link, 'hier'),
        ]);
    }
}
