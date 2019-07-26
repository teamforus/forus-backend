<?php


namespace App\Mail\Vouchers;


use App\Models\Implementation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Voucher extends Mailable
{
    use Queueable, SerializesModels;

    private $email;
    private $fund_product_name;
    private $qr_url;
    private $implementation;

    public function __construct(
        string $email,
        string $fund_product_name,
        string $qr_url
    ) {
        $this->email = $email;
        $this->fund_product_name = $fund_product_name;
        $this->qr_url = $qr_url;
        $this->implementation = Implementation::activeKey();
    }

    public function build(): Mailable
    {
        return $this
            ->from(config('forus.mail.from.no-reply'))
            ->to($this->email)
            ->subject(trans())
            ->view('emails.vouchers.voucher_sent', [
                'fund_product_name' => $this->fund_product_name,
                'qr_url' => $this->qr_url,
                'implementation' => config('forus.mails.implementations.' . $this->implementation)
            ]);
    }
}
