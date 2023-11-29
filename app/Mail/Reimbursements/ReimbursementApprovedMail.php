<?php

namespace App\Mail\Reimbursements;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class ReimbursementApprovedMail extends ImplementationMail
{
    protected string $notificationTemplateKey = "notifications_identities.reimbursement_approved";

    /**
     * @return Mailable
     * @throws CommonMarkException
     */
    public function build(): Mailable
    {
        return $this->buildNotificationTemplatedMail();
    }

    /**
     * @param array $data
     * @return array
     */
    protected function getMailExtraData(array $data): array
    {
        return [
            'app_link' => $this->makeLink($data['app_link'], 'download de Me-app'),
            'webshop_link' => $this->makeLink($data['webshop_link'], 'hier'),
            'webshop_button' => $this->makeButton($data['webshop_link'], 'Activeer tegoed'),
        ];
    }
}
