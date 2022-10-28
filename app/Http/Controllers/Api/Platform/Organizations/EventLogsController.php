<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\EventLog\IndexEventLogRequest;
use App\Http\Resources\Sponsor\EventLogResource;
use App\Models\Organization;
use App\Searches\EmployeeEventLogSearch;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @noinspection PhpUnused
 */
class EventLogsController extends Controller
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

        $search = new EmployeeEventLogSearch($request->employee($organization), $request->only([
            'q', 'loggable', 'loggable_id',
        ]));

        return EventLogResource::queryCollection($search->query(), $request, [
            'employee' => $request->employee($organization),
        ]);
    }
}