<?php

namespace App\Mail\User;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

class FundRequestAssignedBySupervisorMail extends ImplementationMail
{
    protected string $notificationTemplateKey = 'notifications_identities.assigned_to_fund_request_by_supervisor';

    /**
     * @return Mailable
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
            'button' => $this->makeButton($data['button_link'], 'Bekijk aanvraag'),
        ];
    }
}