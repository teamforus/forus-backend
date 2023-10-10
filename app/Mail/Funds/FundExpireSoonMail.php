<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class FundExpireSoonMail extends ImplementationMail
{
    protected string $notificationTemplateKey = 'notifications_identities.voucher_expire_soon_budget';

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
            'webshop_button' => $this->makeButton($data['webshop_link'], 'Ga naar de webshop'),
        ];
    }
}
