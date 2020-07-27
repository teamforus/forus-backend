<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;
use Illuminate\Mail\Mailable;

/**
 * Class ShareProductMail
 * @package App\Mail\Vouchers
 */
class ShareProductVoucherMail extends ImplementationMail
{
    private $transData;

    /**
     * ShareProductVoucherMail constructor.
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
            ->subject(mail_trans('share_product.title', $this->transData['data']))
            ->view('emails.vouchers.share_product', $this->transData['data']);
    }
}
