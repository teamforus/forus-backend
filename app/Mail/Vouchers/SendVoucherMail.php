<?php


namespace App\Mail\Vouchers;

use App\Mail\ImplementationMail;

/**
 * Class VoucherMail
 * @package App\Mail\Vouchers
 */
class SendVoucherMail extends ImplementationMail
{
    private $fundName;
    private $fund_product_name;
    private $qrToken;

    public function __construct(
        string $fund_name,
        string $fund_product_name,
        string $qrToken,
        string $identifier = null
    ) {
        parent::__construct($identifier);

        $this->fundName = $fund_name;
        $this->fund_product_name = $fund_product_name;
        $this->qrToken = $qrToken;
    }

    public function build(): ImplementationMail
    {
        return parent::build()
            ->subject(mail_trans('voucher_sent.title', [
                'fund_name' => $this->fundName
            ]))
            ->view('emails.vouchers.voucher_sent', [
                'fund_name' => $this->fundName,
                'fund_product_name' => $this->fund_product_name,
                'qr_token' => $this->qrToken
            ]);
    }
}
