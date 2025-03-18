<?php

namespace App\Mail\Forus;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class IdentityDestroyRequestMail extends ImplementationMail
{
    public $subject = 'Verzoek tot verwijdering van account';

    /**
     * @throws CommonMarkException
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildSystemMail('identity_destroy_request');
    }
}
