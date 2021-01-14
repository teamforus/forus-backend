<?php

namespace App\Mail\Digest;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BaseDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param array $viewData
     */
    public function __construct($viewData = [])
    {
        $this->viewData = array_merge($this->viewData, $viewData);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): self
    {
        return $this->view('emails.mail-builder-template');
    }
}
