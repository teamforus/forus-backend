<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class ProductReservedRequesterMail extends ImplementationMail
{
    public ?string $notificationTemplateKey = 'notifications_identities.product_voucher_reserved';

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
