<?php

namespace App\Listeners;

use App\Events\BankConnections\BankConnectionActivated;
use App\Events\BankConnections\BankConnectionCreated;
use App\Events\BankConnections\BankConnectionDisabled;
use App\Events\BankConnections\BankConnectionDisabledInvalid;
use App\Events\BankConnections\BankConnectionMonetaryAccountChanged;
use App\Events\BankConnections\BankConnectionRejected;
use App\Events\BankConnections\BankConnectionReplaced;
use App\Events\BankConnections\BaseBankConnectionEvent;
use App\Models\BankConnection;
use App\Models\Employee;
use App\Notifications\Organizations\BankConnections\BankConnectionActivatedNotification;
use App\Notifications\Organizations\BankConnections\BankConnectionDisabledInvalidNotification;
use App\Notifications\Organizations\BankConnections\BankConnectionMonetaryAccountChangedNotification;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Events\Dispatcher;

/**
 * Class BankConnectionSubscriber
 * @package App\Listeners
 */
class BankConnectionSubscriber
{
    /**
     * @param BankConnection $bankConnection
     * @param Employee|null $employee
     * @return array
     */
    protected function getBankConnectionLogModels(
        BankConnection $bankConnection,
        ?Employee $employee
    ): array {
        return [
            'bank' => $bankConnection->bank,
            'employee' => $employee,
            'organization' => $bankConnection->organization,
            'bank_connection' => $bankConnection,
            'bank_connection_account' => $bankConnection->bank_connection_default_account,
        ];
    }

    /**
     * @param BaseBankConnectionEvent $event
     * @param string $eventType
     * @return EventLog
     */
    protected function makeEvent(BaseBankConnectionEvent $event, string $eventType): EventLog
    {
        $logModels = $this->getBankConnectionLogModels(
            $event->getBankConnection(),
            $event->getEmployee()
        );

        return $event->getBankConnection()->log($eventType, $logModels, $event->getData());
    }

    /**
     * @param BankConnectionCreated $event
     * @noinspection PhpUnused
     */
    public function onBankConnectionCreated(BankConnectionCreated $event): void
    {
        $this->makeEvent($event, $event->getBankConnection()::EVENT_CREATED);
    }

    /**
     * @param BankConnectionRejected $event
     * @noinspection PhpUnused
     */
    public function onBankConnectionRejected(BankConnectionRejected $event): void
    {
        $this->makeEvent($event, $event->getBankConnection()::EVENT_REJECTED);
    }

    /**
     * @param BankConnectionReplaced $event
     * @noinspection PhpUnused
     */
    public function onBankConnectionReplaced(BankConnectionReplaced $event): void
    {
        $this->makeEvent($event, $event->getBankConnection()::EVENT_REPLACED);
    }

    /**
     * @param BankConnectionActivated $event
     * @noinspection PhpUnused
     */
    public function onBankConnectionActivated(BankConnectionActivated $event): void
    {
        $eventLog = $this->makeEvent($event, $event->getBankConnection()::EVENT_ACTIVATED);

        BankConnectionActivatedNotification::send($eventLog);
    }

    /**
     * @param BankConnectionDisabled $event
     * @noinspection PhpUnused
     */
    public function onBankConnectionDisabled(BankConnectionDisabled $event): void
    {
        $this->makeEvent($event, $event->getBankConnection()::EVENT_DISABLED);
    }

    /**
     * @param BankConnectionDisabledInvalid $event
     * @noinspection PhpUnused
     */
    public function onBankConnectionDisabledInvalid(BankConnectionDisabledInvalid $event): void
    {
        $eventLog = $this->makeEvent($event, $event->getBankConnection()::EVENT_DISABLED_INVALID);

        BankConnectionDisabledInvalidNotification::send($eventLog);
    }

    /**
     * @param BankConnectionMonetaryAccountChanged $event
     * @noinspection PhpUnused
     */
    public function onBankConnectionMonetaryAccountChanged(BankConnectionMonetaryAccountChanged $event): void
    {
        $eventLog = $this->makeEvent($event, $event->getBankConnection()::EVENT_MONETARY_ACCOUNT_CHANGED);

        BankConnectionMonetaryAccountChangedNotification::send($eventLog);
    }

    /**
     * The events dispatcher
     *
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            BankConnectionCreated::class,
            '\App\Listeners\BankConnectionSubscriber@onBankConnectionCreated'
        );

        $events->listen(
            BankConnectionActivated::class,
            '\App\Listeners\BankConnectionSubscriber@onBankConnectionActivated'
        );

        $events->listen(
            BankConnectionRejected::class,
            '\App\Listeners\BankConnectionSubscriber@onBankConnectionRejected'
        );

        $events->listen(
            BankConnectionDisabled::class,
            '\App\Listeners\BankConnectionSubscriber@onBankConnectionDisabled'
        );

        $events->listen(
            BankConnectionReplaced::class,
            '\App\Listeners\BankConnectionSubscriber@onBankConnectionReplaced'
        );

        $events->listen(
            BankConnectionDisabledInvalid::class,
            '\App\Listeners\BankConnectionSubscriber@onBankConnectionDisabledInvalid'
        );

        $events->listen(
            BankConnectionMonetaryAccountChanged::class,
            '\App\Listeners\BankConnectionSubscriber@onBankConnectionMonetaryAccountChanged'
        );
    }
}
