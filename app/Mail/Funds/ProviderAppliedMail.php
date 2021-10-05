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

    /**
     * @param array $data
     * @return array
     */
    protected function getMailExtraData(array $data): array
    {
        $link = $data['sponsor_dashboard_link'];

        return [
            'sponsor_dashboard_link' => $this->makeLink($link, $link),
            'sponsor_dashboard_button' => $this->makeButton($link, 'Ga naar de beheeromgeving'),
        ];
    }

    /**
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildNotificationTemplatedMail();
    }
}
