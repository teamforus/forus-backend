<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\RecordTypeResource;
use App\Models\VoucherRecord;
use Illuminate\Http\Request;

/**
 * @property-read VoucherRecord $resource
 */
class SponsorVoucherRecordResource extends BaseJsonResource
{
    public const LOAD = [
        'record_type',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return $this->makeResource($this->resource, true);
    }

    /**
     * @param VoucherRecord $voucherRecord
     * @param bool $forSponsor
     * @return array
     */
    protected function makeResource(VoucherRecord $voucherRecord, bool $forSponsor = false): array
    {
        $requesterData = array_merge($voucherRecord->only([
            'value_locale', 'voucher_id',
        ]), [
            'record_type_key' => $voucherRecord->record_type?->key,
            'record_type_name' => $voucherRecord->record_type?->name,
        ]);

        $sponsorData = array_merge($voucherRecord->only([
            'id', 'record_type_id', 'value', 'note',
        ]), [
            'record_type' => new RecordTypeResource($voucherRecord->record_type),
        ], $this->makeTimestamps($voucherRecord->only([
            'created_at', 'updated_at',
        ])));

        return array_merge($requesterData, $forSponsor ? $sponsorData : []);
    }
}
