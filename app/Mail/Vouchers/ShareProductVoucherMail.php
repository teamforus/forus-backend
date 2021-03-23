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
    /**
     * ShareProductVoucherMail constructor.
     * @param array $data
     * @param EmailFrom|null $emailFrom
     */
    public function __construct(array $data = [], ?EmailFrom $emailFrom = null)
    {
        $this->setMailFrom($emailFrom);
        $this->mailData = $this->escapeData($data);
    }

    /**
     * @return string
     */
    protected function getSubject(): string
    {
        return mail_trans("share_product.title_$this->communicationType", $this->mailData);
    }

    /**
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildBase()
            ->subject($this->getSubject())
            ->view('emails.vouchers.share_product', $this->mailData);
    }
}
