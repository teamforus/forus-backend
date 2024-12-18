<?php


namespace App\Services\Forus\Notification\Messages;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class FcmBasicNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private ?string $title;
    private ?string $body;

    /**
     * FcmBasicNotification constructor.
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
    public function via(): array
    {
        return [FcmChannel::class];
    }

    /**
     * @return FcmMessage
     */
    public function toFcm()
    {
        return FcmMessage::create()->setNotification(FcmNotification::create()
            ->setTitle($this->title)
            ->setBody($this->body)
        );
    }
}