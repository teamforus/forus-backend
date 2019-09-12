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
    private $qr_url;

    public function __construct(
        string $fund_name,
        string $fund_product_name,
        string $qr_url,
        string $identifier
    ) {
        parent::__construct($identifier);

        $this->fundName = $fund_name;
        $this->fund_product_name = $fund_product_name;
        $this->qr_url = $qr_url;
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
                'qr_url' => $this->qr_url
            ]);
    }
}
