<?php

namespace App\Http\Resources;

use App\Models\FundPhysicalCardType;
use Illuminate\Http\Request;

/**
 * @property FundPhysicalCardType $resource
 */
class FundPhysicalCardTypeResource extends BaseJsonResource
{
    public const array LOAD = [];
    public const array LOAD_NESTED = [
        'physical_card_type' => PhysicalCardTypeResource::class,
    ];

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $cardType = $this->resource;

        return [
            ...$cardType->only([
                'id', 'fund_id', 'physical_card_type_id',
                'allow_physical_card_linking', 'allow_physical_card_requests', 'allow_physical_card_deactivation',
            ]),
            'physical_card_type' => PhysicalCardTypeResource::create($cardType->physical_card_type),
        ];
    }
}
