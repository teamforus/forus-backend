<?php

namespace App\Mail\User;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class EmployeeAddedMail extends ImplementationMail
{
    public ?string $notificationTemplateKey = 'notifications_identities.added_employee';

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
        $appLink = $data['download_me_app_link'];
        $authLink = $data['dashboard_auth_link'];

        return [
            'download_me_app_link' => $this->makeLink($appLink, 'https://forus.io/DL'),
            'dashboard_auth_link' => $this->makeLink($authLink, 'Ga naar de beheeromgeving'),
            'dashboard_auth_button' => $this->makeButton($authLink, 'Ga naar de beheeromgeving'),
        ];
    }
}
