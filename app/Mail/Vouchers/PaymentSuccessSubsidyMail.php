<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class PaymentSuccessSubsidyMail extends ImplementationMail
{
    protected string $notificationTemplateKey = 'notifications_identities.voucher_subsidy_transaction';

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
            'webshop_button' => $this->makeButton($data['webshop_link'], 'Ga naar webshop'),
        ];
    }
}
