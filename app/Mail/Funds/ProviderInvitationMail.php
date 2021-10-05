<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

/**
 * Class ProviderAppliedMail
 * @package App\Mail\Funds
 */
class ProviderInvitationMail extends ImplementationMail
{
    protected $subjectKey = 'mails/system_mails.provider_invitation.title';

    /**
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildSystemMail('provider_invitation');
    }

    /**
     * @param array $data
     * @return array
     */
    protected function getMailExtraData(array $data): array
    {
        return [
            'invitation_link' => $this->makeLink($data['invitation_link'], 'hier'),
            'invitation_button' => $this->makeButton($data['invitation_link'], 'AANMELDEN'),
        ];
    }
}
