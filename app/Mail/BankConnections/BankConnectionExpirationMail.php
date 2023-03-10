<?php

namespace App\Mail\BankConnections;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;


class BankConnectionExpirationMail extends ImplementationMail
{
    protected string $notificationTemplateKey = "notifications_bank_connections.expiration";

    /**
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildNotificationTemplatedMail();
    }
}
