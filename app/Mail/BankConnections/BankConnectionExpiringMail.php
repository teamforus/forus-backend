<?php

namespace App\Mail\BankConnections;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;


class BankConnectionExpiringMail extends ImplementationMail
{
    protected string $notificationTemplateKey = "notifications_bank_connections.expiring";

    /**
     * @return Mailable
     * @throws CommonMarkException
     */
    public function build(): Mailable
    {
        return $this->buildNotificationTemplatedMail();
    }
}
