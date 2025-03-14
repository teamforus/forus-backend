<?php

namespace App\Mail\BankConnections;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class BankConnectionExpiringMail extends ImplementationMail
{
    public ?string $notificationTemplateKey = 'notifications_bank_connections.expiring';

    /**
     * @throws CommonMarkException
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildNotificationTemplatedMail();
    }
}
