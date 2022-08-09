<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Requests\Api\Platform\Notifications\IndexNotificationsRequest;
use App\Http\Requests\BaseFormRequest;
use App\Models\Notification;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;

class NotificationsController extends Controller
{
    /**
     * @param IndexNotificationsRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(IndexNotificationsRequest $request): AnonymousResourceCollection
    {
        $identity = $request->identity();
        $request = BaseFormRequest::createFrom($request);

        $notifications = Notification::paginateFromRequest($request, $identity);
        $total_unseen = Notification::totalUnseenFromRequest($request, $identity);

        return NotificationResource::collection($notifications)->additional([
            'meta' => compact('total_unseen'),
        ]);
    }
}
