<?php

namespace App\Notifications;

use App\Models\Implementation;
use App\Models\NotificationTemplate;
use App\Services\EventLogService\Models\EventLog;
use App\Services\Forus\Identity\Models\Identity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
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

    protected static $key;
    protected static $scope;
    protected static $pushKey;
    protected static $sendMail =false;
    protected static $sendPush = false;
    protected static $group;

    protected static $visible = false;
    protected static $editable = false;

    protected $eventLog;
    protected $meta = [];

    /**
     * Create a new notification instance.
     *
     * BaseNotification constructor.
     * @param EventLog|null $eventLog
     * @param array $meta
     */
    public function __construct(?EventLog $eventLog, array $meta = [])
    {
        $this->eventLog = $eventLog;
        $this->meta = array_merge($this->meta, $meta);
        $this->queue = config('forus.notifications.notifications_queue_name');
    }

    /**
     * @return bool
     */
    public static function isVisible(): bool
    {
        return static::$visible;
    }

    /**
     * @return bool
     */
    public static function isEditable(): bool
    {
        return static::$editable;
    }

    /**
     * @return string|null
     */
    public static function getKey(): ?string
    {
        return static::$key;
    }

    /**
     * @return string|null
     */
    public static function getScope(): ?string
    {
        return static::$scope;
    }

    /**
     * @return string|null
     */
    public static function getPushKey(): ?string
    {
        return static::$pushKey;
    }

    /**
     * @return mixed
     */
    public static function getGroup()
    {
        return static::$group;
    }

    /**
     * @return array
     */
    public static function getChannels(): array
    {
        $channels = ['database'];

        if (static::$sendMail) {
            $channels[] = 'mail';
        }

        if (static::$sendPush) {
            $channels[] = 'push';
        }

        return $channels;
    }

    /**
     * @return \App\Services\Forus\Notification\NotificationService|\Illuminate\Contracts\Foundation\Application|mixed
     */
    public function getNotificationService()
    {
        return resolve('forus.services.notification');
    }

    /**
     * @param string $email
     * @param Mailable $mailable
     * @return bool
     */
    public function sendMailNotification(string $email, Mailable $mailable): bool
    {
        return $this->getNotificationService()->sendMailNotification($email, $mailable);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array
     * @noinspection PhpUnused
     */
    public function via(): array
    {
        $channelKeys = static::getChannels();
        $channels = ['database'];

        if (in_array('mail', $channelKeys)) {
            $channels[] = MailChannel::class;
        }

        if (in_array('push', $channelKeys)) {
            $channels[] = PushChannel::class;
        }

        return $this->eventLog ? $channels : [];
    }

    /**
     * Serialize and save the notification in the database
     *
     * @return string[]
     * @noinspection PhpUnused
     */
    public function toDatabase(): array
    {
        return array_merge([
            'key' => static::$key,
            'scope' => static::$scope,
            'event_id' => $this->eventLog->id,
        ], $this->meta);
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param Identity $identity
     * @return void
     */
    public function toMail(Identity $identity): void {}

    /**
     * Get the mail representation of the notification.
     *
     * @param Identity $identity
     * @return void
     */
    public function toPush(Identity $identity): void
    {
        $templateFilter = [
            'type' => 'push',
            'key' => static::getKey(),
        ];

        $template = NotificationTemplate::where(array_merge($templateFilter, [
            'implementation_id' => Implementation::general()->id,
        ]))->first();

        $implementationTemplate = NotificationTemplate::where(array_merge($templateFilter, [
            'implementation_id' => Implementation::byKey($this->eventLog->data['implementation_key'])->id ?? null,
        ]))->first() ?: $template;

        if ($implementationTemplate) {
            $this->getNotificationService()->sendPushNotification(
                $identity->address,
                str_var_replace($implementationTemplate->title, $this->eventLog->data),
                str_var_replace($implementationTemplate->content, $this->eventLog->data),
                static::getPushKey()
            );
        }
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
