<?php

namespace App\Notifications\Organizations\BankConnections;

use App\Mail\BankConnections\BankConnectionExpirationMail;
use App\Models\BankConnection;
use App\Models\Identity;
use App\Models\Implementation;

class BankConnectionExpirationNotification extends BaseBankConnectionsNotification
{
    protected static ?string $key = 'notifications_bank_connections.expiration';
    protected static string|array $permissions = ['view_finances', 'manage_bank_connections'];

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var BankConnection $connection */
        $connection = $this->eventLog->loggable;

        $unit = config('forus.bng.notify.expire_time.notification.unit');
        $unitValue = config('forus.bng.notify.expire_time.notification.value');

        $data = array_merge($this->eventLog->data, [
            'sponsor_name' => $connection->organization->name,
            'unit' => trans("notifications/notifications_bank_connections.$unit"),
            'unit_value' => $unitValue,
        ]);

        $mailable = new BankConnectionExpirationMail($data, Implementation::general()->emailFrom());

        $this->sendMailNotification($identity->email, $mailable);
    }
}
