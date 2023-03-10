<?php

namespace App\Listeners;

use App\Events\BankConnections\BankConnectionActivated;
use App\Events\BankConnections\BankConnectionCreated;
use App\Events\BankConnections\BankConnectionDisabled;
use App\Events\BankConnections\BankConnectionDisabledInvalid;
use App\Events\BankConnections\BankConnectionExpiration;
use App\Events\BankConnections\BankConnectionMonetaryAccountChanged;
use App\Events\BankConnections\BankConnectionRejected;
use App\Events\BankConnections\BankConnectionReplaced;
use App\Events\BankConnections\BaseBankConnectionEvent;
use App\Models\BankConnection;
use App\Models\Employee;
use App\Notifications\Organizations\BankConnections\BankConnectionActivatedNotification;
use App\Notifications\Organizations\BankConnections\BankConnectionDisabledInvalidNotification;
use App\Notifications\Organizations\BankConnections\BankConnectionExpirationNotification;
use App\Notifications\Organizations\BankConnections\BankConnectionMonetaryAccountChangedNotification;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Events\Dispatcher;

class BankConnectionSubscriber
{
    /**
     * @param BankConnection $bankConnection
     * @param Employee|null $employee
     * @return array
     * @noinspection PhpUnused
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
     * @param BankConnectionExpiration $event
     * @noinspection PhpUnused
     */
    public function onBankConnectionExpiration(BankConnectionExpiration $event): void
    {
        $eventLog = $this->makeEvent($event, $event->getBankConnection()::EVENT_EXPIRATION);

        BankConnectionExpirationNotification::send($eventLog);
    }

    /**
     * The events dispatcher
     *
     * @param Dispatcher $events
     * @noinspection PhpUnused
     */
    public function subscribe(Dispatcher $events): void
    {
        $class = '\\' . static::class;

        $events->listen(BankConnectionCreated::class, "$class@onBankConnectionCreated");
        $events->listen(BankConnectionActivated::class, "$class@onBankConnectionActivated");
        $events->listen(BankConnectionRejected::class, "$class@onBankConnectionRejected");
        $events->listen(BankConnectionDisabled::class, "$class@onBankConnectionDisabled");
        $events->listen(BankConnectionReplaced::class, "$class@onBankConnectionReplaced");
        $events->listen(BankConnectionDisabledInvalid::class, "$class@onBankConnectionDisabledInvalid");
        $events->listen(BankConnectionMonetaryAccountChanged::class, "$class@onBankConnectionMonetaryAccountChanged");
        $events->listen(BankConnectionExpiration::class, "$class@onBankConnectionExpiration");
    }
}
