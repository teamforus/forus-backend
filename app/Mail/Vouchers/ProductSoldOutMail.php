<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

/**
 * Class ProductSoldOutMail
 * @package App\Mail\Vouchers
 */
class ProductSoldOutMail extends ImplementationMail
{
    protected $notificationTemplateKey = 'notifications_products.sold_out';

    public function build(): Mailable
    {
        $link = $this->mailData['provider_dashboard_link'];

        return $this->buildTemplatedNotification([
            'provider_dashboard_link' => $this->makeLink($link, 'hier'),
            'provider_dashboard_button' => $this->makeButton($link, 'Inloggen'),
        ]);
    }
}
