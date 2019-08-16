<?php

namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;
use bunq\Security\PrivateKey;

class ShareProduct extends ImplementationMail
{
    private $requesterMail;
    private $productName;
    private $qrUrl;
    private $reason;

    public function __construct(
        string $email,
        string $requesterMail,
        string $productName,
        string $qrurl,
        string $reason,
        ?string $identityId)
    {
        parent::__construct($email, $identityId);

        $this->requesterMail = $requesterMail;
        $this->productName = $productName;
        $this->qrUrl = $qrurl;
        $this->reason = $reason;
    }

    public function build(): ImplementationMail
    {
        return $this
            ->from(config('forus.mail.from.no-reply'), config('forus.mail.from.name'))
            ->to($this->email)
            ->subject(implementation_trans('share_product.title', ['requester_email' => $this->requesterMail]))
            ->view('emails.vouchers.share_product', [
                'requester_email' => $this->requesterMail,
                'product_name' => $this->productName,
                'qr_url' => $this->qrUrl,
                'reason' => $this->reason
            ]);
    }
}
