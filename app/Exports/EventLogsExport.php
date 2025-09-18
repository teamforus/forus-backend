<?php

namespace App\Exports;

use App\Exports\Base\BaseFieldedExport;
use App\Models\Employee;
use App\Models\Voucher;
use App\Searches\EmployeeEventLogSearch;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class EventLogsExport extends BaseFieldedExport
{
    protected static string $transKey = 'event_logs';
    protected Employee $employee;

    /**
     * @var array|string[][]
     */
    protected static array $exportFields = [
        'created_at',
        'loggable',
        'event',
        'identity_email',
        'note',
    ];

    /**
     * @param Request $request
     * @param Employee $employee
     * @param array $fields
     */
    public function __construct(Request $request, Employee $employee, array $fields = [])
    {
        $this->fields = $fields;
        $this->employee = $employee;
        $this->data = $this->export($request);
    }

    /**
     * @param Request $request
     * @return Collection
     */
    public function export(Request $request): Collection
    {
        $search = new EmployeeEventLogSearch($this->employee, $request->only([
            'q', 'loggable', 'loggable_id',
        ]));

        $data = $search
            ->query()
            ->with('identity.primary_email')
            ->with('loggable', fn (MorphTo $morphTo) => $morphTo->morphWith([Voucher::class => ['fund']]))
            ->get();

        return $this->exportTransform($data);
    }

    /**
     * @param Collection $data
     * @return Collection
     */
    protected function exportTransform(Collection $data): Collection
    {
        return $this->transformKeys($data->map(fn (EventLog $eventLog) => array_only(
            $this->getRow($eventLog),
            $this->fields,
        )));
    }

    /**
     * @param EventLog $eventLog
     * @return array
     */
    protected function getRow(EventLog $eventLog): array
    {
        return [
            'created_at' => format_date_locale($eventLog->created_at),
            'loggable' => strip_tags($eventLog->loggable_locale_dashboard),
            'event' => strip_tags($eventLog->eventDescriptionLocaleDashboard($this->employee)),
            'identity_email' => $eventLog->getIdentityEmail($this->employee),
            'note' => $eventLog->getNote(),
        ];
    }
}
