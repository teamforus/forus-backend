<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Notifications\IndexNotificationsRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Searches\NotificationSearch;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationsController extends Controller
{
    /**
     * @param IndexNotificationsRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(IndexNotificationsRequest $request): AnonymousResourceCollection
    {
        $seen = $request->input('seen');
        $page = $request->input('page', 1);
        $per_page = $request->input('per_page', 15);
        $mark_read = $request->input('mark_read', false);

        $identity = $request->identity();

        $search = new NotificationSearch([
            $request->only('organization_id'),
            ...compact('seen'),
        ], $identity->notifications()->where('scope', $request->client_type()));

        $notificationsQuery = $search->query();

        if ($mark_read) {
            $listUnreadFetched = $notificationsQuery->clone()
                ->skip(($page - 1) * $per_page)
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
