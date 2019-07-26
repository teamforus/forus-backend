<?php


namespace App\Mail\Vouchers;


use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Voucher extends Mailable
{
    use Queueable, SerializesModels;

    private $email;
    private $fund_product_name;
    private $qr_url;

    public function __construct(
        string $email,
        string $fund_product_name,
        string $qr_url
    ) {
        $this->email = $email;
        $this->fund_product_name = $fund_product_name;
        $this->qr_url = $qr_url;
    }

    public function build(): Mailable
    {
        return $this
            ->from('info@eranmachiels.nl')
            ->to($this->email)
            ->subject(trans('mails.vouchers.voucher_sent.subject'))
            ->view('emails.vouchers.voucher_sent', [
                'fund_product_name' => $this->fund_product_name,
                'qr_url' => $this->qr_url
            ]);
    }
}
