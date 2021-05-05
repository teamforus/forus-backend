<?php

namespace App\Mail\Vouchers;

use App\Mail\MailBodyBuilder;
use App\Models\Voucher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DeactivationVoucherMail extends Mailable
{
    use Queueable, SerializesModels;

    public $viewData = [];
    public $voucher;
    public $deactivationReason;

    public function __construct(Voucher $voucher, string $deactivationReason) {
        $this->voucher = $voucher;
        $this->deactivationReason = $deactivationReason;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): self
    {
        $emailBody = new MailBodyBuilder();

        $emailBody->h1(trans('notifications/notifications_deactivation_voucher.title'));
        $emailBody->text(trans('notifications/notifications_deactivation_voucher.text', [
            'fund_name' => $this->voucher->fund->name,
            'reason' => $this->deactivationReason,
        ]));

        $this->viewData['emailBody'] = $emailBody;

        return $this
            ->view('emails.mail-builder-template')
            ->subject(trans('notifications/notifications_deactivation_voucher.title'));
    }
}
