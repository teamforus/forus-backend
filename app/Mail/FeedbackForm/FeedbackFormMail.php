<?php

namespace App\Mail\FeedbackForm;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class FeedbackFormMail extends ImplementationMail
{
    protected string $subjectKey = 'mails.system_mails.feedback_form.title';

    /**
     * @return Mailable
     * @throws CommonMarkException
     */
    public function build(): Mailable
    {
        return $this->buildSystemMail('feedback_form');
    }
}
