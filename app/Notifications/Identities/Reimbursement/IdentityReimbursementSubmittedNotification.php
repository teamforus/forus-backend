<?php

namespace App\Notifications\Identities\Reimbursement;

use App\Mail\Reimbursements\ReimbursementSubmittedMail;
use App\Models\Identity;
use App\Models\Reimbursement;

/**
 * Notify requester about their fund request being submitted
 */
class IdentityReimbursementSubmittedNotification extends BaseIdentityReimbursementNotification
{
    protected static ?string $key = 'notifications_identities.reimbursement_submitted';

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var Reimbursement $reimbursement */
        $reimbursement = $this->eventLog->loggable;
        $fund = $reimbursement->voucher->fund;

        $this->sendMailNotification(
            $identity->email,
            new ReimbursementSubmittedMail(array_merge($this->eventLog->data, [
                'webshop_link' => $fund->urlWebshop(),
            ]), $fund->getEmailFrom())
        );
    }
}
