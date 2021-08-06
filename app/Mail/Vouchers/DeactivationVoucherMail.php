<?php

namespace App\Mail\Vouchers;

use App\Mail\MailBodyBuilder;
use App\Models\Voucher;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Class DeactivationVoucherMail
 * @package App\Mail\Vouchers
 */
class DeactivationVoucherMail extends Mailable
{
    use Queueable, SerializesModels;

    public $viewData = [];
    public $log;

    /**
     * DeactivationVoucherMail constructor.
     * @param EventLog $log
     */
    public function __construct(EventLog $log)
    {
        $this->log = $log;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): self
    {
        $emailBody = new MailBodyBuilder();

        /** @var Voucher $voucher */
        $data = $this->log->data;
        $voucher = $this->log->loggable;
        $communicationType = $voucher->fund->communicationType();

        $data['date'] = format_date_locale($this->log->created_at);

        $title = mail_trans('voucher_deactivated.title_' . $communicationType, $data);
        $text = mail_trans('voucher_deactivated.description_' . $communicationType, $data);

        $emailBody->h1($title);
        $emailBody->text($text);

        $this->viewData['emailBody'] = $emailBody;

        return $this->view('emails.mail-builder-template')->subject($title);
    }
}