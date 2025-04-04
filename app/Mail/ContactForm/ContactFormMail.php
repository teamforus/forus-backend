<?php

namespace App\Mail\ContactForm;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class ContactFormMail extends ImplementationMail
{
    public $subject = 'New message from contact form';

    /**
     * @throws CommonMarkException
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildSystemMail('contact_form');
    }
}
