<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;

/**
 * Class ProviderAppliedMail
 * @package App\Mail\Funds
 */
class ProviderInvitationMail extends ImplementationMail
{
    protected $subjectKey = 'mails/provider_invitation.title';
    protected $viewKey = 'emails.funds.provider_invitation';
}
