<?php

namespace App\Http\Resources;

use App\Models\FundCriterion;
use App\Models\Implementation;
use App\Models\PreCheck;
use App\Models\PreCheckRecord;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

/**
 * @property-read Implementation $resource
 */
class ImplementationPreChecksResource extends BaseJsonResource
{
    public const LOAD = [
        'pre_checks',
        'pre_checks_records',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $implementation = $this->resource;
        $preChecks = $this->getPreChecks($implementation);
        $preCheckRecords = $this->getPreCheckRecords($implementation);

        return array_map(fn ($step) => [
            ...$step,
            'record_types' => array_values(Arr::sort(Arr::where($preCheckRecords, function($type) use ($step) {
                if ($type['pre_check_id'] === null) {
                    return $step['default'];
                }

                return $type['pre_check_id'] === $step['id'];
            }), 'order')),
        ], $preChecks);
    }

    /**
     * @param Implementation $implementation
     * @return array
     */
    public function getPreChecks(Implementation $implementation): array
    {
        $preChecks = $implementation->pre_checks->sortBy('order');

        return [
            ...$preChecks->map(fn(PreCheck $preCheck) => $preCheck->only([
                'id', 'title', 'title_short', 'description', 'default',
            ]))->toArray(),
            ...$preChecks->where('default', true)->isEmpty() ? [[
                'id' => null,
                'title' => 'Default',
                'title_short' => 'Default',
                'description' => 'Default',
                'default' => true,
            ]] : [],
        ];
    }

    /**
     * @param Implementation $implementation
     * @return array
     */
    protected function getPreCheckRecords(Implementation $implementation): array
    {
        $preChecksRecords = $implementation->pre_checks_records;

        $fundCriteria = FundCriterion::query()
            ->where('optional', false)
            ->whereRelation('fund.fund_config', 'implementation_id', $implementation->id)
            ->groupBy('record_type_key')
            ->get()
            ->groupBy('record_type_key');

        // todo: handle record types with multiple values
        return $fundCriteria->map(function (Collection $fundCriteria) use ($preChecksRecords) {
            /** @var PreCheckRecord $preChecksRecord */
            /** @var FundCriterion $fundCriterion */
            $fundCriterion = $fundCriteria->first();
            $recordType = $fundCriterion->record_type;
            $preChecksRecord = $preChecksRecords->firstWhere('record_type_key', $recordType->key);

            if ($recordType->type == 'string') {
                $values = $fundCriteria
                    ->filter(fn(FundCriterion $fundCriterion) => $fundCriterion->operator == '=')
                    ->pluck('value')->toArray();
            } else {
                $values = [$fundCriterion->value];
            }

            return [
                ...$preChecksRecord ? $preChecksRecord->only([
                    'record_type_key', 'title', 'title_short', 'description', 'order', 'pre_check_id',
                ]) : [
                    'record_type_key' => $recordType->key,
                    'title' => $recordType->name ?? $recordType->key,
                    'title_short' => $recordType->name ?? $recordType->key,
                    'description' => $recordType->name,
                    'order' => 999,
                    'pre_check_id' => null,
                ],
                'value' => $values[0] ?? '',
                'record_type' => RecordTypeResource::create($recordType)->toArray(request()),
            ];
        })->toArray();
    }
}
