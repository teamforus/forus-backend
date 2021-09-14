<?php

namespace App\Mail\User;

use App\Mail\ImplementationMail;

/**
 * Class EmailActivationMail
 * @package App\Mail\User
 */
class IdentityEmailVerificationMail extends ImplementationMail
{
    protected $subjectKey = 'mails/identity_email_verification.title';
    protected $viewKey = 'emails.identity-email-verification';
}
