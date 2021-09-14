<?php

namespace App\Mail\User;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

class EmployeeAddedMail extends ImplementationMail
{
    protected $notificationTemplateKey = 'notifications_identities.added_employee';

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): Mailable
    {
        return $this->buildTemplatedNotification([
            'header_icon' => $this->headerIcon('email_activation.header_image'),
            'dashboard_auth_button' => $this->makeButton($this->mailData['dashboard_auth_link'], 'Ga naar het dashboard'),
            'download_me_app_link' => $this->makeLink($this->mailData['download_me_app_link'], 'https://forus.io/DL'),
        ]);
    }
}
