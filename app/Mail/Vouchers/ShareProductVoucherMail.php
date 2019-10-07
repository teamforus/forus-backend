<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;

/**
 * Class ShareProductMail
 * @package App\Mail\Vouchers
 */
class ShareProductVoucherMail extends ImplementationMail
{
    private $requesterMail;
    private $productName;
    private $qrUrl;
    private $reason;

    public function __construct(
        string $requesterMail,
        string $productName,
        string $qrUrl,
        string $reason,
        string $identityId = null
    ) {
        parent::__construct($identityId);

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
