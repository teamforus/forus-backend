<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class ProviderInvitationMail extends ImplementationMail
{
    public $subject = 'U wordt uitgenodigd voor :fund_name';

    /**
     * @throws CommonMarkException
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
