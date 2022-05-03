<?php

namespace App\Mail\Forus;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

/**
 * Class IdentityDestroyRequestMail
 * @package App\Mail\User
 */
class IdentityDestroyRequestMail extends ImplementationMail
{
    protected $subjectKey = 'mails/system_mails.identity_destroy_request.title';

    /**
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildSystemMail('identity_destroy_request');
    }
}
