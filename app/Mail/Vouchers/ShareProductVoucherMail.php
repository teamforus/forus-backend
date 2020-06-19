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
    private $requesterMail;
    private $productName;
    private $qrToken;
    private $reason;

    public function __construct(
        string $requesterMail,
        string $productName,
        string $qrToken,
        string $reason,
        ?EmailFrom $emailFrom
    ) {
        $this->setMailFrom($emailFrom);;
        $this->requesterMail = $requesterMail;
        $this->productName = $productName;
        $this->qrToken = $qrToken;
        $this->reason = $reason;
    }

    public function build(): Mailable
    {
        return $this->buildBase()
            ->subject(mail_trans('share_product.title', [
                'requester_email' => $this->requesterMail
            ]))
            ->view('emails.vouchers.share_product', [
                'requester_email' => $this->requesterMail,
                'product_name' => $this->productName,
                'qr_token' => $this->qrToken,
                'reason' => $this->reason
            ]);
    }
}
