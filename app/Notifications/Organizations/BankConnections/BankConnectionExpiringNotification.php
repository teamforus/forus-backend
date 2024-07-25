<?php

namespace App\Notifications\Organizations\BankConnections;

use App\Mail\BankConnections\BankConnectionExpiringMail;
use App\Models\Identity;
use App\Models\Implementation;

class BankConnectionExpiringNotification extends BaseBankConnectionsNotification
{
    protected static ?string $key = 'notifications_bank_connections.expiring';
    protected static string|array $permissions = 'manage_bank_connections';

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        $implementation = Implementation::general();

        $mailable = new BankConnectionExpiringMail([
            'url_sponsor' => $implementation->urlSponsorDashboard(),
            ...$this->eventLog->data,
        ], $implementation->emailFrom());

        $this->sendMailNotification($identity->email, $mailable, $this->eventLog);
    }
}
