<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class ProductBoughtProviderMail extends ImplementationMail
{
    public ?string $notificationTemplateKey = 'notifications_products.reserved';

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
        $link = $data['provider_dashboard_link'];

        return [
            'provider_dashboard_link' => $this->makeLink($link, 'hier', '#315EFD'),
            'provider_dashboard_button' => $this->makeButton($link, 'GA NAAR DE BEHEEROMGEVING', '#315EFD'),
        ];
    }
}
