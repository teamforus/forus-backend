<?php

namespace App\Mail\ContactForm;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

class ContactFormMail extends ImplementationMail
{
    protected string $subjectKey = 'mails/system_mails.contact_form.title';

    /**
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildSystemMail('contact_form');
    }
}
