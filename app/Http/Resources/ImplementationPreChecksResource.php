<?php

namespace App\Http\Resources;

use App\Models\Implementation;
use App\Models\PreCheck;
use App\Models\PreCheckRecord;
use App\Models\RecordType;
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
        $preChecks = $implementation->pre_checks->sortBy('order');
        $preChecksRecords = $implementation->pre_checks_records;

        $usedRecordTypes = RecordType::whereRelation('fund_criteria.fund.fund_config', [
            'implementation_id' => $implementation->id,
        ])->get();

        $preChecks = [
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

        $preCheckRecords = $usedRecordTypes->map(function (RecordType $recordType) use ($preChecksRecords) {
            /** @var PreCheckRecord $preChecksRecord */
            $preChecksRecord = $preChecksRecords->firstWhere('record_type_key', $recordType->key);

            return [
                ...$preChecksRecord ? $preChecksRecord->only([
                    'record_type_key', 'title', 'title_short', 'description', 'order', 'pre_check_id',
                ]) : [
                    'record_type_key' => $recordType->key,
                    'title' => $recordType->name,
                    'title_short' => $recordType->name,
                    'description' => $recordType->name,
                    'order' => 999,
                    'pre_check_id' => null,
                ],
                'record_type' => RecordTypeResource::create($recordType)->toArray(request()),
            ];
        })->toArray();

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
}
