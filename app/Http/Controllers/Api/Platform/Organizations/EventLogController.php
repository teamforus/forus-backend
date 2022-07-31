<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\EventLog\IndexEventLogRequest;
use App\Http\Resources\Sponsor\EventLogResource;
use App\Models\Organization;
use App\Scopes\Builders\EventLogQuery;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @noinspection PhpUnused
 */
class EventLogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexEventLogRequest $request
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function index(
        IndexEventLogRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', [EventLog::class, $organization]);

        $query = EventLogQuery::queryEvents(EventLog::query(), $organization, $request);

        if ($q = $request->get('q')) {
            EventLogQuery::whereQueryFilter($query, $q);
        }

        return EventLogResource::queryCollection($query->orderByDesc('created_at'), $request);
    }
}