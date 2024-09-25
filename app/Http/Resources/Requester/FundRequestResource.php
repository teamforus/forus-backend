<?php

namespace App\Http\Resources\Requester;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\FundRequestClarificationResource;
use App\Http\Resources\Tiny\FundTinyResource;
use App\Http\Resources\VoucherResource;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use App\Models\Voucher;

/**
 * @property FundRequest $resource
 */
class FundRequestResource extends BaseJsonResource
{
    /**
     * @var string[]
     */
    public const LOAD = [
        'fund.logo.presets',
        'records.record_type.translations',
        'records.fund_request_clarifications.files.preview.presets',
        'records.fund_request_clarifications.fund_request_record.record_type.translations',
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
            'id', 'fund_id', 'contact_information', 'note', 'state', 'state_locale',
        ]), [
            'fund' => new FundTinyResource($this->resource->fund),
            'records' => $this->getRecordsDetails($this->resource),
            'vouchers' => VoucherResource::collection($this->resource->vouchers),
            'payoutVoucher' => new VoucherResource(Voucher::query()->where([
                'fund_request_id' => $this->resource->id,
                'voucher_type' => Voucher::VOUCHER_TYPE_PAYOUT,
            ])->first()),
        ], $this->makeTimestamps($this->resource->only([
            'created_at', 'updated_at',
        ])));
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
                'id', 'state', 'record_type_key', 'fund_request_id', 'value',
            ]), [
                'record_type' => $record->record_type->only('id', 'key', 'type', 'system', 'name'),
                'clarifications' => FundRequestClarificationResource::collection(
                    $record->fund_request_clarifications->sortByDesc('created_at')
                ),
            ], $this->timestamps($this->resource, 'created_at', 'updated_at'));
        })->toArray();
    }
}
