<?php

namespace App\Mail\Digest;

class DigestProviderFundsMail extends BaseDigestMail
{
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): self
    {
        return $this->view('emails.mail-digest')->subject('Nieuwe notificaties omtrent aangesloten fondsen');
    }
}
