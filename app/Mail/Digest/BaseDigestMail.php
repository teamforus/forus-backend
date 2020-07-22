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
}
