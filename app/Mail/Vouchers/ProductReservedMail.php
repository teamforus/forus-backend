<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;
use Illuminate\Mail\Mailable;

/**
 * Class ProductReservedMail
 * @package App\Mail\Vouchers
 */
class ProductReservedMail extends ImplementationMail
{
    private $transData;

    public function __construct(
        array $data = [],
        ?EmailFrom $emailFrom = null
    ) {
        $this->setMailFrom($emailFrom);

        $this->transData['data'] = $data;
    }

    public function build(): Mailable
    {
        return $this->buildBase()
            ->subject(mail_trans('product_bought.title', $this->transData['data']))
            ->view('emails.funds.product_bought', $this->transData['data']);
    }
}
