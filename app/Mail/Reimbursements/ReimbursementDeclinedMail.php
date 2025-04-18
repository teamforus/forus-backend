<?php

namespace App\Mail\Reimbursements;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class ReimbursementDeclinedMail extends ImplementationMail
{
    public ?string $notificationTemplateKey = 'notifications_identities.reimbursement_declined';

    /**
     * @throws CommonMarkException
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildNotificationTemplatedMail();
    }
}
