<?php

namespace App\Mail\Vouchers;


use App\Mail\ImplementationMail;

class AssignedVoucherMail extends ImplementationMail
{
    private $fundName;
    private $qrToken;
    private $voucher_amount;
    private $voucher_expire_minus_day;

    /**
     * Create a new message instance.
     *
     * AssignedVoucherMail constructor.
     * @param string $fund_name
     * @param string $qrToken
     * @param int $voucher_amount
     * @param string $voucher_expire_minus_day
     * @param string|null $identifier
     */
    public function __construct(
        string $fund_name,
        string $qrToken,
        int $voucher_amount,
        string $voucher_expire_minus_day,
        string $identifier = null
    ) {
        parent::__construct($identifier);

        $this->fundName = $fund_name;
        $this->qrToken = $qrToken;
        $this->voucher_amount = $voucher_amount;
        $this->voucher_expire_minus_day = $voucher_expire_minus_day;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): ImplementationMail
    {
        return parent::build()
            ->subject(mail_trans('voucher_assigned.title', [
                'fund_name' => $this->fundName
            ]))
            ->view('emails.vouchers.voucher_assigned', [
                'fund_name' => $this->fundName,
                'qr_token'  => $this->qrToken,
                'voucher_amount' => $this->voucher_amount,
                'voucher_expire_minus_day' => $this->voucher_expire_minus_day,
            ]);
    }
}
