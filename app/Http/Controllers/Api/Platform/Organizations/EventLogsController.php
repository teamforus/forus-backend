<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Exports\EventLogsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\EventLog\IndexEventLogRequest;
use App\Http\Resources\Arr\ExportFieldArrResource;
use App\Http\Resources\Sponsor\EventLogResource;
use App\Models\Organization;
use App\Searches\EmployeeEventLogSearch;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @noinspection PhpUnused
     */
    public function index(
        IndexEventLogRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', [EventLog::class, $organization]);

        $search = new EmployeeEventLogSearch($request->employee($organization), $request->only([
            'q', 'loggable', 'loggable_id',
        ]), EventLog::query());

        return EventLogResource::queryCollection($search->query(), $request, [
            'employee' => $request->employee($organization),
        ]);
    }

    /**
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return AnonymousResourceCollection
     * @noinspection PhpUnused
     */
    public function getExportFields(
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAny', [EventLog::class, $organization]);

        return ExportFieldArrResource::collection(EventLogsExport::getExportFields());
    }

    /**
     * @param IndexEventLogRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @return BinaryFileResponse
     */
    public function export(
        IndexEventLogRequest $request,
        Organization $organization
    ): BinaryFileResponse {
        $this->authorize('show', $organization);
        $this->authorize('viewAny', [EventLog::class, $organization]);

        $exportType = $request->input('data_format', 'xls');
        $fileName = date('Y-m-d H:i:s') . '.' . $exportType;
        $fields = $request->input('fields', EventLogsExport::getExportFieldsRaw());

        $search = new EmployeeEventLogSearch($request->employee($organization), $request->only([
            'q', 'loggable', 'loggable_id',
        ]), EventLog::query());

        $exportData = new EventLogsExport($search->query(), $fields, $request->employee($organization));

        return resolve('excel')->download($exportData, $fileName);
    }
}
