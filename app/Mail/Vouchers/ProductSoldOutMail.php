<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class ProductSoldOutMail extends ImplementationMail
{
    public ?string $notificationTemplateKey = 'notifications_products.sold_out';

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
        return [
            'provider_dashboard_link' => $this->makeLink($data['provider_dashboard_link'], 'hier'),
            'provider_dashboard_button' => $this->makeButton($data['provider_dashboard_link'], 'Inloggen'),
        ];
    }
}
