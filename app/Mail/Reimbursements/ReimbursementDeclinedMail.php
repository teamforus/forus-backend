<?php

namespace App\Mail\Reimbursements;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

class ReimbursementDeclinedMail extends ImplementationMail
{
    protected string $notificationTemplateKey = 'notifications_identities.reimbursement_declined';

    /**
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildNotificationTemplatedMail();
    }
}
