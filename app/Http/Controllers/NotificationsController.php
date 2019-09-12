<?php

namespace App\Http\Controllers;

use App\Services\Forus\Notification\Interfaces\INotificationRepo;
use Illuminate\View\View;

/**
 * Class NotificationsController
 * @package App\Http\Controllers\Api\Platform
 */
class NotificationsController extends Controller
{
    private $notificationRepo;

    /**
     * NotificationsController constructor.
     * @param INotificationRepo $notificationRepo
     */
    public function __construct(
        INotificationRepo $notificationRepo
    ) {
        $this->notificationRepo = $notificationRepo;
        $this->middleware('throttle', [10, 1]);
    }

    /**
     * @param string $token
     * @return View
     */
    public function unsubscribe(
        string $token
    ): View {
        $email = $this->notificationRepo->emailByUnsubscribeToken($token) ??
            abort('404');

        $reSubLink = $this->notificationRepo->makeReSubLink($email, $token);
        $this->notificationRepo->unsubscribeEmail($email);

        return view('pages.notifications.unsubscribed', compact(
            'email', 'reSubLink'
        ));
    }

    /**
     * @param string $token
     * @return View
     */
    public function subscribe(
        string $token
    ): View {
        $email = $this->notificationRepo->emailByUnsubscribeToken(
                $token
            ) ?? abort(404);

        $unSubLink = $this->notificationRepo->makeUnsubLink($email, $token);
        $this->notificationRepo->reSubscribeEmail($email);

        return view('pages.notifications.subscribed', compact(
            'email', 'unSubLink'
        ));
    }
}
