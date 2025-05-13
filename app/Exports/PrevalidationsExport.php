<?php

namespace App\Exports;

use App\Exports\Base\BaseFieldedExport;
use App\Http\Requests\BaseFormRequest;
use App\Models\Prevalidation;
use App\Models\PrevalidationRecord;
use Illuminate\Support\Collection;

class PrevalidationsExport extends BaseFieldedExport
{
    protected static string $transKey = 'prevalidations';

    /**
     * @var array|string[]
     */
    protected static array $exportFields = [
        'code',
        'used',
        'records',
    ];

    /**
     * @param BaseFormRequest $request
     * @param array $fields
     */
    public function __construct(BaseFormRequest $request, protected array $fields)
    {
        $this->data = $this->export($request);
    }

    /**
     * @param BaseFormRequest $request
     * @return Collection
     */
    public function export(BaseFormRequest $request): Collection
    {
        $query = Prevalidation::search($request)->with([
            'prevalidation_records.record_type.translations',
        ]);

        (clone $query)->update([
            'exported' => true,
        ]);

        return $this->exportTransform($query->get());
    }

    /**
     * @param Collection $data
     * @return Collection
     */
    protected function exportTransform(Collection $data): Collection
    {
        $fieldLabels = array_pluck(static::getExportFields(), 'name', 'key');

        return $data->map(function (Prevalidation $prevalidation) use ($fieldLabels) {
            $row = array_only($this->getRow($prevalidation), $this->fields);

            $row = array_reduce(array_keys($row), fn ($obj, $key) => array_merge($obj, [
                $fieldLabels[$key] => $row[$key],
            ]), []);

            $records = in_array('records', $this->fields) ? static::getRecords($prevalidation) : [];

            return [...$row, ...$records];
        })->values();
    }

    /**
     * @param Prevalidation $prevalidation
     * @return array
     */
    protected function getRow(Prevalidation $prevalidation): array
    {
        return [
            'code' => $prevalidation->uid,
            'used' => trans('export.prevalidations.used_' . ($prevalidation->state === Prevalidation::STATE_USED ? 'yes' : 'no')),
        ];
    }

    /**
     * @param Prevalidation $prevalidation
     * @return array
     */
    protected static function getRecords(Prevalidation $prevalidation): array
    {
        return $prevalidation->prevalidation_records->filter(function (PrevalidationRecord $record) {
            return !str_contains($record->record_type->key, '_eligible');
        })->pluck('value', 'record_type.name')->toArray();
    }
}
