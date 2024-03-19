<?php

namespace App\Mail\Funds\FundRequestRecords;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class FundRequestRecordDeclinedMail extends ImplementationMail
{
    protected string $notificationTemplateKey = "notifications_identities.fund_request_record_declined";

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
     * @psalm-return array{webshop_link: string, webshop_button: string}
     */
    protected function getMailExtraData(array $data): array
    {
        return [
            'webshop_link' => $this->makeLink($data['webshop_link'], $data['webshop_link']),
            'webshop_button' => $this->makeButton($data['webshop_link'], 'WEBSHOP'),
        ];
    }
}
