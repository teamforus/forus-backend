<?php

namespace App\Mail\Funds\FundRequestRecords;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class FundRequestRecordDeclinedMail extends ImplementationMail
{
    public ?string $notificationTemplateKey = "notifications_identities.fund_request_record_declined";

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
            'webshop_link' => $this->makeLink($data['webshop_link'], $data['webshop_link']),
            'webshop_button' => $this->makeButton($data['webshop_link'], 'WEBSHOP'),
        ];
    }
}
