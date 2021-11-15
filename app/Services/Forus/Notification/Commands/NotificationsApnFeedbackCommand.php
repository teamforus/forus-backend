<?php

namespace App\Services\Forus\Notification\Commands;

use App\Services\Forus\Notification\NotificationService;
use Illuminate\Console\Command;
use NotificationChannels\Apn\ApnFeedback;
use NotificationChannels\Apn\FeedbackService;

class NotificationsApnFeedbackCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.notifications:apn-feedback';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Request apn feedback to remove old tokens.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle() {
        try {
            $feedbackService = app(FeedbackService::class);
            $notificationService = resolve('forus.services.notification');

            /** @var ApnFeedback $feedback */
            foreach ($feedbackService->get() as $feedback) {
                $notificationService->removeNotificationToken($feedback->token, NotificationService::TYPE_PUSH_IOS);
            }
        } catch (\Exception $e) {}
    }
}
