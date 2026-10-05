<?php

namespace App\Http\Resources;

use App\Models\FundRequestRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @property FundRequestRecord $resource
 */
class FundRequestRecordResource extends BaseJsonResource
{
    public const array LOAD = [
        'record_type.translation',
        'record_type.record_type_options.translations',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->resource->only([
                'id', 'record_type_key', 'fund_request_id',
                'fund_criterion_id',
            ]),
            'value' => $this->resource->record_type_key === 'wallet_bsn'
                ? Str::mask($this->resource->value, '*', 0, -4)
                : $this->resource->value,
            'record_type' => [
                ...$this->resource->record_type->only([
                    'key', 'name', 'type',
                ]),
                'name' => $this->resource->record_type?->name ?: $this->resource->record_type?->key,
                'options' => $this->resource->record_type?->getOptions(),
            ],
            $this->makeTimestamps($this->resource->only([
                'created_at', 'updated_at',
            ])),
        ];
    }
}
