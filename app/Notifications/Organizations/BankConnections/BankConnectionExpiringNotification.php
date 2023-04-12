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

        $this->sendMailNotification($identity->email, new BankConnectionExpiringMail(array_merge([
            'url_sponsor' => $implementation->urlSponsorDashboard(),
        ], $this->eventLog->data), $implementation->emailFrom()));
    }
}
