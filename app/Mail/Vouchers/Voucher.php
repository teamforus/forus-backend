<?php


namespace App\Mail\Vouchers;


use App\Mail\ImplementationMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Voucher extends ImplementationMail
{
    private $fundName;
    private $fund_product_name;
    private $qr_url;

    public function __construct(
        string $email,
        string $fund_name,
        string $fund_product_name,
        string $qr_url,
        string $identifier
    ) {
        parent::__construct($email, $identifier);

        $this->fundName = $fund_name;
        $this->fund_product_name = $fund_product_name;
        $this->qr_url = $qr_url;
    }

    public function build(): ImplementationMail
    {
        return $this
            ->from(config('forus.mail.from.no-reply'), config('forus.mail.from.name'))
            ->to($this->email)
            ->subject(mail_trans('voucher_sent.title'))
            ->view('emails.vouchers.voucher_sent', [
                'fund_name' => $this->fundName,
                'fund_product_name' => $this->fund_product_name,
                'qr_url' => $this->qr_url
            ]);
    }
}
