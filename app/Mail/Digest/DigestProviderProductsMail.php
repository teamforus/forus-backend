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
        return $this->view('emails.mail-digest')->subject('Update: Nieuwe aanbieding reserveringen');
    }
}
