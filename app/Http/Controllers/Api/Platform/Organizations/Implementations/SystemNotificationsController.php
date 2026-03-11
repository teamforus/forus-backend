<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Implementations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Implementations\SystemNotifications\IndexSystemNotificationsRequest;
use App\Http\Requests\Api\Platform\Organizations\Implementations\SystemNotifications\UpdateSystemNotificationsRequest;
use App\Http\Resources\SystemNotificationResource;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\SystemNotification;
use App\Models\SystemNotificationConfig;
use App\Services\Forus\Notification\Repositories\NotificationRepo;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SystemNotificationsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexSystemNotificationsRequest $request
     * @param Organization $organization
     * @param Implementation $implementation
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(
        IndexSystemNotificationsRequest $request,
        Organization $organization,
        Implementation $implementation
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAny', [Implementation::class, $organization]);
        $this->authorize('view', [$implementation, $organization]);

        $notificationRepo = resolve(NotificationRepo::class);
        $notifications = $notificationRepo->getSystemNotificationsQuery(true);

        return SystemNotificationResource::queryCollection($notifications, $request);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Implementation $implementation
     * @param SystemNotification $systemNotification
     * @return SystemNotificationResource
     */
    public function show(
        Organization $organization,
        Implementation $implementation,
        SystemNotification $systemNotification
    ): SystemNotificationResource {
        $this->authorize('show', $organization);
        $this->authorize('viewAny', [Implementation::class, $organization]);
        $this->authorize('view', [$implementation, $organization]);

        return SystemNotificationResource::create($systemNotification, [
            'withLastSentData' => true,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param UpdateSystemNotificationsRequest $request
     * @param Organization $organization
     * @param Implementation $implementation
     * @param SystemNotification $systemNotification
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return SystemNotificationResource
     */
    public function update(
        UpdateSystemNotificationsRequest $request,
        Organization $organization,
        Implementation $implementation,
        SystemNotification $systemNotification
    ): SystemNotificationResource {
        $this->authorize('show', $organization);
        $this->authorize('viewAny', [Implementation::class, $organization]);
        $this->authorize('view', [$implementation, $organization]);
        $this->authorize('updateCMS', [$implementation, $organization]);

        $fundId = $request->input('fund_id');

        if ($systemNotification->optional && $request->hasAny(SystemNotificationConfig::ENABLE_FIELDS)) {
            $systemNotification->updateConfig(
                $implementation,
                $request->only(SystemNotificationConfig::ENABLE_FIELDS),
                $fundId,
            );
        }

        $systemNotification->syncTemplates($implementation, $request->input('templates', []), $fundId);
        $systemNotification->removeTemplates($implementation, $request->input('templates_remove', []), $fundId);

        return SystemNotificationResource::create($systemNotification, [
            'withLastSentData' => true,
        ]);
    }
}
