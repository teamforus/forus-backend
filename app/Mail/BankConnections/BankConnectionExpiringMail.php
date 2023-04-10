<?php

namespace App\Mail\BankConnections;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;


class BankConnectionExpiringMail extends ImplementationMail
{
    protected string $notificationTemplateKey = "notifications_bank_connections.expiring";

    /**
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildNotificationTemplatedMail();
    }
}
