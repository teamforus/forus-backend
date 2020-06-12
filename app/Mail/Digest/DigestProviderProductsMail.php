<?php

namespace App\Mail\Digest;

class DigestProviderProductsMail extends BaseDigestMail
{
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): self
    {
        return $this->view('emails.mail-digest')->subject('Nieuwe notificaties omtrent uw producten');
    }
}
