<?php

namespace App\Mail\Funds\FundRequestClarifications;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

/**
 * Notify requester about fund request clarification being requested by the sponsor/validator.
 */
class FundRequestClarificationRequestedMail extends ImplementationMail
{
    public ?string $notificationTemplateKey = 'notifications_identities.fund_request_feedback_requested';

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
        $linkTitle = 'Bekijk de aanvraag';
        $question = $data['fund_request_clarification_question'] ?? '';
        $link = $data['webshop_clarification_link'];

        return [
            'fund_request_clarification_question' => nl2br(e($question)),
            'webshop_clarification_link' => $this->makeLink($link, $linkTitle),
            'webshop_clarification_button' => $this->makeButton($link, $linkTitle),
        ];
    }
}
