<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class SendVoucherMail extends ImplementationMail
{
    public $subject = 'Hierbij ontvangt u uw :fund_name';

    /**
     * @return Mailable
     * @throws CommonMarkException
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
