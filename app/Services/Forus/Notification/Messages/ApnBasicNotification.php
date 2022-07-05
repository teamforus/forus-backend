<?php


namespace App\Services\Forus\Notification\Messages;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Apn\ApnChannel;
use NotificationChannels\Apn\ApnMessage;

/**
 * Class ApnBasicNotification
 * @package App\Services\Forus\Notification\Messages
 */
class ApnBasicNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private ?string $title;
    private ?string $body;

    /**
     * ApnBasicNotification constructor.
     * @param string|null $title
     * @param string|null $body
     */
    public function __construct(
        string $title = null,
        string $body = null
    ) {
        $this->title = $title;
        $this->body = $body;
    }

    /**
     * @return array
     */
    public function via()
    {
        return [ApnChannel::class];
    }

    /**
     * @return ApnMessage
     */
    public function toApn()
    {
        return ApnMessage::create(
            $this->title,
            $this->body
        )->sound();
    }
}