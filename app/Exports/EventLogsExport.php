<?php

namespace App\Exports;

use App\Exports\Base\BaseExport;
use App\Models\Employee;
use App\Models\Voucher;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;

class EventLogsExport extends BaseExport
{
    protected static string $transKey = 'event_logs';

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
     * @param Builder|Relation|EventLog $builder
     * @param array $fields
     * @param Employee $employee
     */
    public function __construct(
        Builder|Relation|EventLog $builder,
        protected array $fields,
        protected Employee $employee,
    ) {
        parent::__construct($builder, $fields);
    }

    /**
     * @return array
     */
    protected function getBuilderWithArray(): array
    {
        return [
            'identity.primary_email',
            'loggable' => fn (MorphTo $morphTo) => $morphTo->morphWith([Voucher::class => ['fund']]),
        ];
    }

    /**
     * @param Model|EventLog $model
     * @return array
     */
    protected function getRow(Model|EventLog $model): array
    {
        return [
            'created_at' => format_date_locale($model->created_at),
            'loggable' => strip_tags($model->loggable_locale_dashboard),
            'event' => strip_tags($model->eventDescriptionLocaleDashboard($this->employee)),
            'identity_email' => $model->getIdentityEmail($this->employee),
            'note' => $model->getNote(),
        ];
    }
}
