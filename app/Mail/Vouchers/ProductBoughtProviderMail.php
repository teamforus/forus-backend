<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class ProductBoughtProviderMail extends ImplementationMail
{
    protected string $notificationTemplateKey = 'notifications_products.reserved';

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
     * @psalm-return array{provider_dashboard_link: string, provider_dashboard_button: string}
     */
    protected function getMailExtraData(array $data): array
    {
        return [
            'provider_dashboard_link' => $this->makeLink($data['provider_dashboard_link'], 'hier'),
            'provider_dashboard_button' => $this->makeButton($data['provider_dashboard_link'], 'Inloggen'),
        ];
    }
}
