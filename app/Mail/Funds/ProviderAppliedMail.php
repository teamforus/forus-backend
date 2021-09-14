<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

/**
 * Class ProviderAppliedMail
 * @package App\Mail\Funds
 */
class ProviderAppliedMail extends ImplementationMail
{
    protected $notificationTemplateKey = "notifications_funds.provider_applied";

    public function build(): Mailable
    {
        $link = $this->mailData['sponsor_dashboard_link'];

        return $this->buildTemplatedNotification([
            'sponsor_dashboard_link' => $this->makeLink($link, $link),
            'sponsor_dashboard_button' => $this->makeButton($link, 'Ga naar de beheeromgeving'),
        ]);
    }
}
