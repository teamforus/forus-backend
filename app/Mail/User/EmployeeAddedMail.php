<?php

namespace App\Mail\User;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

class EmployeeAddedMail extends ImplementationMail
{
    protected $notificationTemplateKey = 'notifications_identities.added_employee';

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
        $appLink = $data['download_me_app_link'];
        $authLink = $data['dashboard_auth_link'];

        return [
            'header_icon' => $this->headerIcon('header_image'),
            'download_me_app_link' => $this->makeLink($appLink, 'https://forus.io/DL'),
            'dashboard_auth_link' => $this->makeLink($authLink, 'Ga naar het dashboard'),
            'dashboard_auth_button' => $this->makeButton($authLink, 'Ga naar het dashboard'),
        ];
    }
}
