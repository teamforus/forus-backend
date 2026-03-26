<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class SendProductVoucherBySponsorMail extends ImplementationMail
{
    public ?string $notificationTemplateKey = 'notifications_identities.sponsor_product_voucher_shared_by_email';

    /**
     * @throws CommonMarkException
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
            ...$data['qr_token'] ? ['qr_token' => $this->makeQrCode($data['qr_token'])] : [],
        ];
    }
}
