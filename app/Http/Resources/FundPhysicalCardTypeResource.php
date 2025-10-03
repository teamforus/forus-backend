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

    /**
     * @param string|null $append
     * @return array
     */
    public static function load(?string $append = null): array
    {
        return [
            ...parent::load($append),
            ...PhysicalCardTypeResource::load("$append.physical_card_type"),
        ];
    }

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
