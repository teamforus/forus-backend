<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Implementations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Implementations\SystemNotifications\IndexSystemNotificationsRequest;
use App\Http\Requests\Api\Platform\Organizations\Implementations\SystemNotifications\ShowSystemNotificationRequest;
use App\Http\Requests\Api\Platform\Organizations\Implementations\SystemNotifications\UpdateSystemNotificationsRequest;
use App\Http\Resources\SystemNotificationResource;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\SystemNotification;
use App\Services\Forus\Notification\Repositories\NotificationRepo;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;

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
     * @param ShowSystemNotificationRequest $request
     * @param Organization $organization
     * @param Implementation $implementation
     * @param SystemNotification $systemNotification
     * @return SystemNotificationResource
     */
    public function show(
        ShowSystemNotificationRequest $request,
        Organization $organization,
        Implementation $implementation,
        SystemNotification $systemNotification
    ): SystemNotificationResource {
        $this->authorize('show', $organization);
        $this->authorize('viewAny', [Implementation::class, $organization]);
        $this->authorize('view', [$implementation, $organization]);

        $funds = $organization->funds;

        return SystemNotificationResource::create($systemNotification, [
            'fundIds' => (array) $request->get('fund_id') ?: $funds->pluck('id')->toArray(),
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

        $systemNotification->system_notification_configs()->updateOrCreate([
            'implementation_id' => $implementation->id,
        ], $request->only('enable_all', 'enable_mail', 'enable_push', 'enable_database'));

        foreach ($request->input('templates', []) as $template) {
            $systemNotification->templates()->updateOrCreate(array_merge([
                'implementation_id' => $implementation->id,
                'fund_id' => null,
                'type' => $template['type'],
                'formal' => $template['formal'],
            ], $implementation->allow_per_fund_notification_templates ? [
                'fund_id' => $template['fund_id'] ?? null,
            ] : []), Arr::only($template, [
                'title', 'content',
            ]));
        }

        foreach ($request->input('templates_remove', []) as $template) {
            $systemNotification->templates()->where(array_merge([
                'implementation_id' => $implementation->id,
                'fund_id' => null,
                'type' => $template['type'],
                'formal' => $template['formal'],
            ], $implementation->allow_per_fund_notification_templates ? [
                'fund_id' => $template['fund_id'] ?? null,
            ] : []))->delete();
        }

        return SystemNotificationResource::create($systemNotification);
    }
}
