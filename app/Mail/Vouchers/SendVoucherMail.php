<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;

/**
 * Class VoucherMail
 * @package App\Mail\Vouchers
 */
class SendVoucherMail extends ImplementationMail
{
    protected $subjectKey = 'mails/voucher_sent.title';
    protected $viewKey = 'emails.vouchers.voucher_sent';
}