<?php

namespace App\Notifications;

use App\Services\EventLogService\Models\EventLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;

/**
 * Class BaseNotification
 * @package App\Notifications
 */
abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const SCOPE_WEBSHOP = 'webshop';
    public const SCOPE_SPONSOR = 'sponsor';
    public const SCOPE_PROVIDER = 'provider';
    public const SCOPE_VALIDATOR = 'validator';

    protected $key;
    protected $eventLog;
    protected $scope;
    protected $meta = [];
    protected $sendMail = false;

    /**
     * Create a new notification instance.
     *
     * BaseNotification constructor.
     * @param EventLog $eventLog
     * @param array $meta
     */
    public function __construct(EventLog $eventLog, array $meta = [])
    {
        $this->queue = config('forus.notifications.notifications_queue_name');
        $this->meta = array_merge($this->meta, $meta);
        $this->eventLog = $eventLog;
    }

    /**
     * @return \App\Services\Forus\Notification\NotificationService|\Illuminate\Contracts\Foundation\Application|mixed
     */
    public function getNotificationService()
    {
        return notification_service();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array
     */
    public function via(): array
    {
        $channels = ['database'];

        if ($this->sendMail) {
            $channels[] = MailChannel::class;
        }

        return $channels;
    }

    /**
     * Serialize and save the notification in the database
     *
     * @return string[]
     */
    public function toDatabase(): array
    {
        return array_merge([
            'key' => $this->key,
            'scope' => $this->scope,
            'event_id' => $this->eventLog->id,
        ], $this->meta);
    }

    /**
     * Generate and send notifications for EventLog instance
     *
     * @param EventLog $event
     * @return bool
     */
    public static function send(EventLog $event): bool
    {
        try {
            $identities = static::eligibleIdentities($event->loggable);
            $meta = new static($event, static::getMeta($event->loggable));

            \Illuminate\Support\Facades\Notification::send($identities, $meta);
        } catch (\Exception $exception) {
            if ($logger = logger()) {
                $logger->error(sprintf(
                    "Unable to create notification:\n %s \n %s",
                    $exception->getMessage(),
                    $exception->getTraceAsString()
                ));
            }

            return false;
        }

        return true;
    }

    /**
     * Get additional data to store in notification
     *
     * @param Model $loggable
     * @return array
     * @throws \Exception
     */
    abstract public static function getMeta($loggable): array;

    /**
     * Get identities which are eligible for the notification
     *
     * @param Model $loggable
     * @return Collection
     * @throws \Exception
     */
    abstract public static function eligibleIdentities($loggable): Collection;
}
