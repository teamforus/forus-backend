<?php

namespace App\Mail\User;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class FundRequestAssignedBySupervisorMail extends ImplementationMail
{
    protected string $notificationTemplateKey = 'notifications_identities.assigned_to_fund_request_by_supervisor';

    /**
     * @throws CommonMarkException
     */
    public function build(): Mailable|null
    {
        return $this->buildNotificationTemplatedMail();
    }

    /**
     * @param array $data
     *
     * @return string[]
     *
     * @psalm-return array{button: string}
     */
    protected function getMailExtraData(array $data): array
    {
        return [
            'button' => $this->makeButton($data['button_link'], 'Bekijk aanvraag'),
        ];
    }
}