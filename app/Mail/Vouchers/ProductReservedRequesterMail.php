<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

/**
 * Class ProductReservedUserMail
 * @package App\Mail\Vouchers
 */
class ProductReservedRequesterMail extends ImplementationMail
{
    protected string $notificationTemplateKey = 'notifications_identities.product_voucher_reserved';

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
            'qr_token' => $this->makeQrCode($data['qr_token']),
        ];
    }
}
