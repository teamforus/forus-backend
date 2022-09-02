<?php

namespace App\Mail\Digest;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BaseDigestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, InteractsWithQueue;

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

    /**
     * Handle a job failure.
     *
     * @param \Throwable $e
     * @return void
     */
    public function failed(\Throwable $e): void
    {
        if ($logger = logger()) {
            $logger->error("Error sending digest: `" . $e->getMessage() . "`");
        }
    }
}
