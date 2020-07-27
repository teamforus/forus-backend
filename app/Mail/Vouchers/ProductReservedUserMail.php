<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;
use Illuminate\Mail\Mailable;

/**
 * Class ProductReservedUserMail
 * @package App\Mail\Vouchers
 */
class ProductReservedUserMail extends ImplementationMail
{
    private $transData;

    /**
     * ProductReservedUserMail constructor.
     * @param array $data
     * @param EmailFrom|null $emailFrom
     */
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
            ->subject(mail_trans('product_reserved.title', $this->transData['data']))
            ->view('emails.funds.product_reserved', $this->transData['data']);
    }
}
