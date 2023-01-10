<?php

namespace App\Notifications\Identities\Reimbursement;

use App\Mail\Reimbursements\ReimbursementApprovedMail;
use App\Models\Identity;
use App\Models\Reimbursement;

class IdentityReimbursementApprovedNotification extends BaseIdentityReimbursementNotification
{
    protected static ?string $key = 'notifications_identities.reimbursement_approved';

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var Reimbursement $reimbursement */
        $reimbursement = $this->eventLog->loggable;
        $fund = $reimbursement->voucher->fund;

        $mailable = new ReimbursementApprovedMail(array_merge($this->eventLog->data, [
            'app_link'      => 'https://www.forus.io/DL',
            'webshop_link'  => $fund->urlWebshop('/funds'),
        ]), $fund->getEmailFrom());

        $this->sendMailNotification($identity->email, $mailable);
    }
}
