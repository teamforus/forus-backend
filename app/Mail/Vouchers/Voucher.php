<?php


namespace App\Mail\Vouchers;


use App\Mail\ImplementationMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Voucher extends ImplementationMail
{
    private $fund_product_name;
    private $qr_url;

    public function __construct(
        string $email,
        string $fund_product_name,
        string $qr_url,
        string $identifier
    ) {
        parent::__construct($email, $identifier);

        $this->fund_product_name = $fund_product_name;
        $this->qr_url = $qr_url;
    }

    public function build(): ImplementationMail
    {
        return $this
            ->from(config('forus.mail.from.no-reply'))
            ->to($this->email)
            ->subject()
            ->view('emails.vouchers.voucher_sent', [
                'fund_product_name' => $this->fund_product_name,
                'qr_url' => $this->qr_url
            ]);
    }
}
