<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Requests\Api\Platform\Notifications\IndexNotificationsRequest;
use App\Models\Implementation;
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
        $seen = $request->input('seen');
        $per_page = $request->input('per_page', 15);
        $mark_read = $request->input('mark_read', false);

        $identity = $request->identity();
        $notificationsQuery = Notification::search($request, $seen, $identity->notifications());

        if ($mark_read) {
            $listUnreadFetched = $notificationsQuery->clone()
                ->take($per_page)
                ->get()
                ->whereNull('read_at')
                ->pluck('id');

            Notification::query()
                ->whereIn('id', $listUnreadFetched)
                ->update(['read_at' => now()]);
        }

        $total_unseen = Notification::totalUnseenFromRequest($request, $identity);

        return NotificationResource::queryCollection($notificationsQuery, $per_page)->additional([
            'meta' => compact('total_unseen'),
        ]);
    }
}
