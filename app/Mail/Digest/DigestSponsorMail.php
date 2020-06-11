<?php

namespace App\Mail\Digest;

class DigestSponsorMail extends BaseDigestMail
{
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): self
    {
        return $this->view('emails.mail-digest')->subject('DAILY SPONSOR DIGEST');
    }
}
