<?php

namespace App\Mail\Share;

use App\Mail\ImplementationMail;
use App\Mail\MailBodyBuilder;
use Illuminate\Mail\Mailable;

/**
 * Class ShareAppMail
 * @package App\Mail\Share
 */
class ShareAppMail extends ImplementationMail
{
    protected $subjectKey = 'share/email.me_app_download_link.title';

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): Mailable
    {
        $data = $this->getTransData();
        $subject = $this->getSubject(trans($this->subjectKey, $data));

        $emailBody = new MailBodyBuilder();

        $emailBody->h1(trans('share/email.me_app_download_link.title'));
        $emailBody->text(trans('share/email.me_app_download_link.line_1'));
        $emailBody->link(trans('share/email.me_app_download_link.link'));
        $emailBody->text(trans('share/email.me_app_download_link.line_2'));

        $this->viewData['emailBody'] = $emailBody;

        return $this->view('emails.mail-builder-template')->subject($subject);
    }
}
