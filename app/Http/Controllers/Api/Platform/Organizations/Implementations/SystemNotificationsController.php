<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Implementations;

use App\Http\Controllers\Controller;
use App\Http\Resources\ImplementationNotificationResource;
use App\Models\Implementation;
use App\Models\Organization;
use App\Services\Forus\Notification\Repositories\NotificationRepo;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SystemNotificationsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Organization $organization
     * @param Implementation $implementation
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        Organization $organization,
        Implementation $implementation
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAny', [Implementation::class, $organization]);
        $this->authorize('view', [$implementation, $organization]);

        $notificationRepo = resolve(NotificationRepo::class);
        $notifications = $notificationRepo->getNotificationsTypes(true);

        return ImplementationNotificationResource::collection($notifications);
    }
}
