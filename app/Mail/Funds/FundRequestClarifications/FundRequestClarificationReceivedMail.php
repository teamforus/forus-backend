<?php

namespace App\Mail\Funds\FundRequestClarifications;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class FundRequestClarificationReceivedMail extends ImplementationMail
{
    public ?string $notificationTemplateKey = 'notifications_fund_requests.clarification_received';

    /**
     * @throws CommonMarkException
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
            'validator_fund_request_button' => $this->makeButton(
                $data['validator_fund_request_link'],
                'Ga naar de beheeromgeving'
            ),
        ];
    }
}
