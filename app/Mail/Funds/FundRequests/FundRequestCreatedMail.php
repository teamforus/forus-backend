<?php

namespace App\Mail\Funds\FundRequests;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

/**
 * Class FundRequestCreatedMail
 * @package App\Mail\FundRequests
 */
class FundRequestCreatedMail extends ImplementationMail
{
    protected $notificationTemplateKey = "notifications_identities.fund_request_created";

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
            'webshop_link' => $this->makeLink($data['webshop_link'], 'hier'),
            'webshop_button' => $this->makeButton($data['webshop_link'], 'Ga naar de webshop'),
        ];
    }
}
