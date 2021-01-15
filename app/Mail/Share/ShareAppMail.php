<?php

namespace App\Mail\Share;

use App\Mail\MailBodyBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Class ShareAppMail
 * @package App\Mail\Share
 */
class ShareAppMail extends Mailable
{
    use Queueable, SerializesModels;

    public $viewData = [];

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): self
    {
        $emailBody = new MailBodyBuilder();

        $emailBody->h1(trans('share/email.me_app_download_link.title'));
        $emailBody->text(trans('share/email.me_app_download_link.line_1'));
        $emailBody->link(trans('share/email.me_app_download_link.link'));
        $emailBody->text(trans('share/email.me_app_download_link.line_2'));

        $this->viewData['emailBody'] = $emailBody;

        return $this
            ->view('emails.mail-builder-template')
            ->subject(trans('share/email.me_app_download_link.title'));
    }
}
