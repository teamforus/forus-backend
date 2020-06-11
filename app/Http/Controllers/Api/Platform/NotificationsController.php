<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Requests\Api\Platform\Notifications\IndexCountNotificationsRequest;
use App\Http\Requests\Api\Platform\Notifications\IndexNotificationsRequest;
use App\Models\Notification;
use \Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Scopes\Builders\EventLogQuery;
use App\Services\Forus\Identity\Models\Identity;

/**
 * Class NotificationsController
 * @package App\Http\Controllers\Api\Platform
 */
class NotificationsController extends Controller
{
    /**
     * @param IndexNotificationsRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(
        IndexNotificationsRequest $request
    ): AnonymousResourceCollection {
        $identity = Identity::findByAddress(auth_address()) or abort(403);

        $notifications = Notification::paginateFromRequest($request, $identity);
        $total_unseen = Notification::totalUnseenFromRequest($request, $identity);

        return NotificationResource::collection($notifications)->additional([
            'meta' => compact('total_unseen'),
        ]);
    }
}
