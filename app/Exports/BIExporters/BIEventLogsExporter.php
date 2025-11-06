<?php

namespace App\Exports\BIExporters;

use App\Exports\EventLogsExport;
use App\Searches\EmployeeEventLogSearch;
use App\Services\BIConnectionService\Exporters\BaseBIExporter;
use App\Services\EventLogService\Models\EventLog;

class BIEventLogsExporter extends BaseBIExporter
{
    protected string $key = 'event_logs';
    protected string $name = 'Activiteitenlogboek';

    /**
     * @return array
     */
    public function toArray(): array
    {
        $employee = $this->organization->findEmployee($this->organization->identity_address);

        $search = new EmployeeEventLogSearch($employee, [
            'loggable' => ['fund', 'bank_connection', 'employees'],
        ], EventLog::query());

        $export = new EventLogsExport($search->query(), EventLogsExport::getExportFieldsRaw(), $employee);

        return $export->collection()->toArray();
    }
}
