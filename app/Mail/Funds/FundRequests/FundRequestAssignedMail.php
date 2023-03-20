<?php

namespace App\Mail\Funds\FundRequests;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

class FundRequestAssignedMail extends ImplementationMail
{
    protected string $subjectKey = 'mails/system_mails.fund_request_assigned.title';

    /**
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildSystemMail('fund_request_assigned');
    }

    /**
     * @param array $data
     * @return array
     */
    protected function getMailExtraData(array $data): array
    {
        return [
            'button' => $this->makeButton($data['button_link'], 'View request'),
        ];
    }
}