<?php

namespace App\Mail\FeedbackForm;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class FeedbackFormMail extends ImplementationMail
{
    public $subject = 'New feedback from customer :customer_email';

    /**
     * @return Mailable
     * @throws CommonMarkException
     */
    public function build(): Mailable
    {
        return $this->buildSystemMail('feedback_form');
    }
}
