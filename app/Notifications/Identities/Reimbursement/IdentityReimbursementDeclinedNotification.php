<?php

namespace App\Notifications\Identities\Reimbursement;

use App\Mail\Reimbursements\ReimbursementDeclinedMail;
use App\Models\Identity;
use App\Models\Reimbursement;

class IdentityReimbursementDeclinedNotification extends BaseIdentityReimbursementNotification
{
    protected static ?string $key = 'notifications_identities.reimbursement_declined';

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var Reimbursement $reimbursement */
        $reimbursement = $this->eventLog->loggable;
        $emailFrom = $reimbursement->voucher->fund->getEmailFrom();
        $mailable = new ReimbursementDeclinedMail($this->eventLog->data, $emailFrom);

        $this->sendMailNotification($identity->email, $mailable);
    }
}
