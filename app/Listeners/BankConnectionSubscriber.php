<?php

namespace App\Listeners;

use App\Events\BankConnections\BankConnectionActivated;
use App\Events\BankConnections\BankConnectionCreated;
use App\Events\BankConnections\BankConnectionDisabled;
use App\Events\BankConnections\BankConnectionDisabledInvalid;
use App\Events\BankConnections\BankConnectionExpiring;
use App\Events\BankConnections\BankConnectionMonetaryAccountChanged;
use App\Events\BankConnections\BankConnectionRejected;
use App\Events\BankConnections\BankConnectionReplaced;
use App\Events\BankConnections\BaseBankConnectionEvent;
use App\Mail\BankConnections\BankConnectionExpiringMail;
use App\Models\BankConnection;
use App\Models\Employee;
use App\Models\Implementation;
use App\Models\OrganizationContact;
use App\Notifications\Organizations\BankConnections\BankConnectionActivatedNotification;
use App\Notifications\Organizations\BankConnections\BankConnectionDisabledInvalidNotification;
use App\Notifications\Organizations\BankConnections\BankConnectionExpiringNotification;
use App\Notifications\Organizations\BankConnections\BankConnectionMonetaryAccountChangedNotification;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Events\Dispatcher;

class BankConnectionSubscriber
{


    /**
     * @param BaseBankConnectionEvent $event
     * @param string $eventType
     * @return EventLog
     * @noinspection PhpUnused
     */
    protected function makeEvent(BaseBankConnectionEvent $event, string $eventType): EventLog
    {
        return $event->getBankConnection()->log(
            $eventType,
            $event->getBankConnection()->getLogModels($event->getEmployee()),
            $event->getData()
        );
    }
}
