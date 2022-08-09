<?php

namespace App\Http\Controllers\Api\Platform\Notifications;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Notifications\UpdateNotificationPreferencesRequest;
use App\Http\Requests\BaseFormRequest;
use App\Models\Identity;
use App\Services\Forus\Notification\Interfaces\INotificationRepo;
use Illuminate\Http\JsonResponse;

/**
 * Class NotificationsController
 * @package App\Http\Controllers\Api\Platform
 */
class NotificationsSettingsController extends Controller
{
    private INotificationRepo $notificationRepo;

    /**
     * @param INotificationRepo $notificationServiceRepo
     */
    public function __construct(INotificationRepo $notificationServiceRepo)
    {
        $this->notificationRepo = $notificationServiceRepo;
    }

    /**
     * Get identity notification preferences
     * @param BaseFormRequest $request
     * @return JsonResponse
     */
    public function index(BaseFormRequest $request): JsonResponse {

        $identity = $request->identity();
        $email = $identity->email;
        $email_unsubscribed = $email && $this->notificationRepo->isEmailUnsubscribed($email);
        $preferences = $this->notificationRepo->getNotificationPreferences($identity->address);

        return new JsonResponse([
            'data' => compact('email_unsubscribed', 'preferences', 'email')
        ]);
    }

    /**
     * @param UpdateNotificationPreferencesRequest $request
     * @return JsonResponse
     */
    public function update(UpdateNotificationPreferencesRequest $request): JsonResponse
    {
        $identity = $request->identity();
        $email = $identity->email;

        if ($request->input('email_unsubscribed')) {
            $this->notificationRepo->unsubscribeEmail($email);
        } else {
            $this->notificationRepo->reSubscribeEmail($email);
        }

        $email_unsubscribed = $this->notificationRepo->isEmailUnsubscribed($email);
        $preferences = $request->input('preferences', []);
        $preferences = $this->notificationRepo->updateIdentityPreferences($identity->address, $preferences);

        return new JsonResponse([
            'data' => compact('email_unsubscribed', 'preferences', 'email')
        ]);
    }
}
