<?php

namespace App\Notifications;

use App\Services\EventLogService\Models\EventLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;

abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const SCOPE_WEBSHOP = 'webshop';
    public const SCOPE_SPONSOR = 'sponsor';
    public const SCOPE_PROVIDER = 'provider';
    public const SCOPE_VALIDATOR = 'validator';

    protected $key;
    protected $eventId;
    protected $scope = null;
    protected $meta = [];

    /**
     * Create a new notification instance.
     *
     * BaseNotification constructor.
     * @param $eventId
     * @param array $meta
     */
    public function __construct($eventId, array $meta = [])
    {
        $this->queue = config('forus.notifications.notifications_queue_name');
        $this->meta = array_merge($this->meta, $meta);
        $this->eventId = $eventId;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    abstract public function via($notifiable): array;

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
            'event_id' => $this->eventId,
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
            \Illuminate\Support\Facades\Notification::send(
                static::eligibleIdentities($event->loggable),
                new static($event->id, static::getMeta($event->loggable))
            );
        } catch (\Exception $exception) {
            if ($logger = logger()) {
                $logger->error(sprintf(
                    "Unable to create notification:\n %s",
                    $exception->getMessage())
                );
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
