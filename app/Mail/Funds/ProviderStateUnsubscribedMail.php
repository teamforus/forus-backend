<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class ProviderStateUnsubscribedMail extends ImplementationMail
{
    public ?string $notificationTemplateKey = 'notifications_fund_providers.state_unsubscribed';

    /**
     * @throws CommonMarkException
     * @return Mailable
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
            'sponsor_dashboard_link' => $this->makeLink($link, 'hier', '#315EFD'),
            'sponsor_dashboard_button' => $this->makeButton($link, 'GA NAAR DE BEHEEROMGEVING', '#315EFD'),
        ];
    }
}
