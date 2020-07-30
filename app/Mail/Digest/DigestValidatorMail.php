<?php

namespace App\Mail\Digest;

class DigestValidatorMail extends BaseDigestMail
{
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): self
    {
        return $this->view('emails.mail-digest')->subject(trans('digests/validator.subject'));
    }
}
