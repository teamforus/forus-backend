<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;

/**
 * Class ShareProductMail
 * @package App\Mail\Vouchers
 */
class ShareProductMail extends ImplementationMail
{
    private $requesterMail;
    private $productName;
    private $qrUrl;
    private $reason;

    public function __construct(
        string $email,
        string $requesterMail,
        string $productName,
        string $qrUrl,
        string $reason,
        ?string $identityId)
    {
        parent::__construct($email, $identityId);

        $this->requesterMail = $requesterMail;
        $this->productName = $productName;
        $this->qrUrl = $qrUrl;
        $this->reason = $reason;
    }

    public function build(): ImplementationMail
    {
        return parent::build()
            ->subject(mail_trans('share_product.title', [
                'requester_email' => $this->requesterMail
            ]))
            ->view('emails.vouchers.share_product', [
                'requester_email' => $this->requesterMail,
                'product_name' => $this->productName,
                'qr_url' => $this->qrUrl,
                'reason' => $this->reason
            ]);
    }
}
