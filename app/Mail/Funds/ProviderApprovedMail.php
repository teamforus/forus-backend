<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class ProviderApprovedMail extends ImplementationMail
{
    public ?string $notificationTemplateKey = 'notifications_fund_providers.approved_budget';

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
        $link = $data['provider_dashboard_link'];

        return [
            'provider_dashboard_button' => $this->makeButton($link, 'DASHBOARD'),
            'provider_dashboard_link' => $this->makeLink($link, 'hier'),
        ];
    }
}
