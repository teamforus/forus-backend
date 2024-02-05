<?php

namespace App\Mail\Funds\FundRequests;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class FundRequestCreatedMail extends ImplementationMail
{
    protected string $notificationTemplateKey = "notifications_identities.fund_request_created";

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
            'webshop_link' => $this->makeLink($data['webshop_link'], 'hier'),
            'webshop_button' => $this->makeButton($data['webshop_link'], 'Ga naar de webshop'),
        ];
    }
}
