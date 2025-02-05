<?php

namespace App\Http\Resources\Requester;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\FundRequestClarificationResource;
use App\Http\Resources\Tiny\FundTinyResource;
use App\Http\Resources\VoucherResource;
use App\Http\Resources\VoucherTransactionPayoutResource;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * @property FundRequest $resource
 */
class FundRequestResource extends BaseJsonResource
{
    /**
     * @var string[]
     */
    public const array LOAD = [
        'fund.logo.presets',
        'records.record_type.translations',
        'records.fund_request_clarifications.files.preview.presets',
        'records.fund_request_clarifications.fund_request_record.record_type.translations',
        'vouchers.transactions',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->resource->only([
                'id', 'fund_id', 'contact_information', 'note', 'state', 'state_locale',
            ]),
            'fund' => [
                ...(new FundTinyResource($this->resource->fund))->toArray($request),
                ...$this->resource->fund->translateColumns($this->resource->fund->only('name')),
            ],
            'records' => $this->getRecordsDetails($this->resource),
            'payouts' => VoucherTransactionPayoutResource::collection($this->getPayouts($this->resource)),
            'vouchers' => VoucherResource::collection($this->getVouchers($this->resource)),
            ...$this->makeTimestamps($this->resource->only([
                'created_at', 'updated_at',
            ])),
        ];
    }

    /**
     * @param FundRequest $fundRequest
     * @return Collection
     */
    public function getVouchers(FundRequest $fundRequest): Collection
    {
        return $fundRequest->vouchers->where('voucher_type', Voucher::VOUCHER_TYPE_VOUCHER);
    }

    /**
     * @param FundRequest $fundRequest
     * @return Collection
     */
    public function getPayouts(FundRequest $fundRequest): Collection
    {
        return $fundRequest->vouchers
            ->where('voucher_type', Voucher::VOUCHER_TYPE_PAYOUT)
            ->map(fn ($voucher) => $voucher->transactions->where(
                'target', VoucherTransaction::TARGET_PAYOUT,
            ))
            ->flatten();
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
