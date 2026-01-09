<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\MediaResource;
use App\Models\PhysicalCard;
use Illuminate\Http\Request;

/**
 * @property-read PhysicalCard $resource
 */
class SponsorPhysicalCardResource extends BaseJsonResource
{
    public const array LOAD = [
        'voucher',
        'physical_card_type.photo.presets',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $physicalCard = $this->resource;

        return [
            ...$physicalCard->only([
                'id', 'code', 'code_locale', 'physical_card_type_id',
            ]),
            'photo' => $physicalCard->physical_card_type->photo
                ? new MediaResource($physicalCard->physical_card_type->photo)
                : null,
            'voucher' => SponsorVoucherResource::create($physicalCard?->voucher),
            'physical_card_type' => new SponsorPhysicalCardTypeResource($physicalCard?->physical_card_type),
            ...$this->makeTimestamps($physicalCard->only([
                'created_at', 'updated_at',
            ])),
        ];
    }
}
