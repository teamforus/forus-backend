<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class ProviderStateRejectedMail extends ImplementationMail
{
    protected string $notificationTemplateKey = 'notifications_fund_providers.state_rejected';

    /**
     * @throws CommonMarkException
     */
    public function build(): Mailable|null
    {
        return $this->buildNotificationTemplatedMail();
    }

    /**
     * @param array $data
     *
     * @return string[]
     *
     * @psalm-return array{provider_dashboard_button: string, provider_dashboard_link: string}
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
