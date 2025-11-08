<?php

namespace App\Http\Resources;

use App\Models\PhysicalCard;
use Illuminate\Http\Request;

/**
 * @property-read PhysicalCard $resource
 * @property-read bool $include_voucher_details
 */
class PhysicalCardResource extends BaseJsonResource
{
    public const array LOAD = [
        'physical_card_type.photo',
    ];

    /**
     * @param string|null $append
     * @return array
     */
    public static function load(?string $append = null): array
    {
        return [
            ...parent::load($append),
            ...VoucherResource::load('voucher'),
        ];
    }

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
            ...$this->include_voucher_details ? [
                'voucher' => VoucherResource::create($physicalCard->voucher),
            ] : [],
            'photo' => $physicalCard->physical_card_type->photo
                ? new MediaResource($physicalCard->physical_card_type->photo)
                : null,
        ];
    }
}
