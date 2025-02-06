<?php

namespace App\Mail\Forus;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class IdentityDestroyRequestMail extends ImplementationMail
{
    public $subject = 'Verzoek tot verwijdering van account';

    /**
     * @return Mailable
     * @throws CommonMarkException
     */
    public function build(): Mailable
    {
        return $this->buildSystemMail('identity_destroy_request');
    }
}
