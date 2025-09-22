<?php

namespace App\Exports\BIExporters;

use App\Exports\EventLogsExport;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\EventLog\IndexEventLogRequest;
use App\Services\BIConnectionService\Exporters\BaseBIExporter;

class BIEventLogsExporter extends BaseBIExporter
{
    protected string $key = 'event_logs';
    protected string $name = 'Activiteitenlogboek';

    /**
     * @return array
     */
    public function toArray(): array
    {
        $fields = EventLogsExport::getExportFieldsRaw();
        $employee = $this->organization->findEmployee($this->organization->identity_address);

        $request = (new IndexEventLogRequest())->merge([
            'loggable' => ['fund', 'bank_connection', 'employees'],
        ]);

        $data = new EventLogsExport($request, $employee, $fields);

        return $data->collection()->toArray();
    }
}
