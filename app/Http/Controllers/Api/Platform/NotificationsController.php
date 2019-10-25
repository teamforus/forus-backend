<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platfrom\Notifications\UpdateNotificationPreferencesRequest;
use App\Services\Forus\Notification\Interfaces\INotificationRepo;
use App\Services\Forus\Record\Repositories\Interfaces\IRecordRepo;
use Illuminate\Http\JsonResponse;

/**
 * Class NotificationsController
 * @package App\Http\Controllers\Api\Platform
 */
class NotificationsController extends Controller
{
    private $notificationRepo;
    private $recordRepo;

    /**
     * NotificationsController constructor.
     * @param INotificationRepo $notificationServiceRepo
     * @param IRecordRepo $recordRepo
     */
    public function __construct(
        INotificationRepo $notificationServiceRepo,
        IRecordRepo $recordRepo
    ) {
        $this->notificationRepo = $notificationServiceRepo;
        $this->recordRepo = $recordRepo;
    }

    /**
     * Get identity notification preferences
     * @return JsonResponse
     * @throws \Exception
     */
    public function index(): JsonResponse {
        $id = auth()->id();

        $email = $this->recordRepo->primaryEmailByAddress($id);
        $email_unsubscribed = $this->notificationRepo->isEmailUnsubscribed($email);
        $preferences = $this->notificationRepo->getNotificationPreferences($id);

        return response()->json([
            'data' => compact('email_unsubscribed', 'preferences', 'email')
        ]);
    }

    /**
     * @param UpdateNotificationPreferencesRequest $request
     * @return JsonResponse
     */
    public function update(
        UpdateNotificationPreferencesRequest $request
    ): JsonResponse {
        $id = auth()->id();
        $email = $this->recordRepo->primaryEmailByAddress($id);

        if ($request->input('email_unsubscribed')) {
            $this->notificationRepo->unsubscribeEmail($email);
        } else {
            $this->notificationRepo->reSubscribeEmail($email);
        }

        $email_unsubscribed = $this->notificationRepo->isEmailUnsubscribed($email);
        $preferences = $request->input('preferences', []);
        $preferences = $this->notificationRepo->updateIdentityPreferences(
            $id, array_pluck($preferences, 'subscribed', 'key')
        );

        return response()->json([
            'data' => compact('email_unsubscribed', 'preferences', 'email')
        ]);
    }
}
