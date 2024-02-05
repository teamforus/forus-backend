<?php

namespace App\Mail\Reimbursements;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class ReimbursementDeclinedMail extends ImplementationMail
{
    protected string $notificationTemplateKey = 'notifications_identities.reimbursement_declined';

    /**
     * @return Mailable
     * @throws CommonMarkException
     */
    public function build(): Mailable
    {
        return $this->buildNotificationTemplatedMail();
    }
}
