<?php

namespace App\Http\Resources\Requester;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\FundRequestClarificationResource;
use App\Http\Resources\Tiny\FundTinyResource;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;

/**
 * @property FundRequest $resource
 */
class FundRequestResource extends BaseJsonResource
{
    /**
     * @var string[]
     */
    public const LOAD = [
        'records.record_type.translations',
        'records.fund_request_clarifications.files.preview.presets',
        'records.fund_request_clarifications.fund_request_record.record_type.translations',
        'fund.logo.presets',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return array_merge($this->resource->only([
            'id', 'state', 'employee_id', 'fund_id', 'contact_information', 'note', 'state_locale',
        ]), [
            'fund' => new FundTinyResource($this->resource->fund),
            'records' => $this->getRecordsDetails($this->resource),
        ], $this->timestamps($this->resource, 'created_at', 'updated_at'));
    }

    /**
     * @param FundRequest $fundRequest
     * @return array
     */
    public function getRecordsDetails(FundRequest $fundRequest): array
    {
        $bsnFields = ['bsn', 'partner_bsn', 'bsn_hash', 'partner_bsn_hash'];

        return $fundRequest->records->filter(function(FundRequestRecord $record) use ($bsnFields) {
            return !in_array($record->record_type_key, $bsnFields, true);
        })->map(function(FundRequestRecord $record) {
            return array_merge($record->only([
                'id', 'state', 'record_type_key', 'fund_request_id', 'employee_id', 'value',
                'fund_criterion_id',
            ]), [
                'record_type' => $record->record_type->only('id', 'key', 'type', 'system', 'name'),
                'clarifications' => FundRequestClarificationResource::collection(
                    $record->fund_request_clarifications->sortByDesc('created_at')
                ),
            ], $this->timestamps($this->resource, 'created_at', 'updated_at'));
        })->toArray();
    }
}
