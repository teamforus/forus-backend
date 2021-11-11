<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

/**
 * Class ProductReservedMail
 * @package App\Mail\Vouchers
 */
class ProductBoughtProviderMail extends ImplementationMail
{
    protected $notificationTemplateKey = 'notifications_products.reserved';

    /**
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
        return [
            'provider_dashboard_link' => $this->makeLink($data['provider_dashboard_link'], 'hier'),
            'provider_dashboard_button' => $this->makeButton($data['provider_dashboard_link'], 'Inloggen'),
        ];
    }
}
