<?php

namespace App\Mail\ProductReservations;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class ProductReservationAcceptedMail extends ImplementationMail
{
    protected string $notificationTemplateKey = 'notifications_identities.product_reservation_accepted';

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
            'webshop_link' => $this->makeLink($data['webshop_link'], 'website'),
            'webshop_button' => $this->makeLink($data['webshop_link'], 'website'),
        ];
    }
}