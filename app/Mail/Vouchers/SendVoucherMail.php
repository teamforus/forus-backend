<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

/**
 * Class VoucherMail
 * @package App\Mail\Vouchers
 */
class SendVoucherMail extends ImplementationMail
{
    protected $subjectKey = 'mails/system_mails.voucher_send_to_email.title';

    /**
     * @return Mailable
     */
    public function build(): Mailable
    {
        return parent::buildSystemMail('voucher_send_to_email');
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
