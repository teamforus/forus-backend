<?php

namespace App\Mail\Forus;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class IdentityDestroyRequestMail extends ImplementationMail
{
    protected string $subjectKey = 'mails/system_mails.identity_destroy_request.title';

    /**
     * @return Mailable
     * @throws CommonMarkException
     */
    public function build(): Mailable
    {
        return $this->buildSystemMail('identity_destroy_request');
    }
}
