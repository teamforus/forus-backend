<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class ProviderAppliedMail extends ImplementationMail
{
    protected string $notificationTemplateKey = "notifications_funds.provider_applied";

    /**
     * @return Mailable
     * @throws CommonMarkException
     */
    public function build(): Mailable
    {
        return $this->buildNotificationTemplatedMail();
    }

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
}
