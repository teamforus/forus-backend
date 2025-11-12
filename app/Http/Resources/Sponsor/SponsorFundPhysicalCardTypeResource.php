<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\BaseJsonResource;
use App\Models\FundPhysicalCardType;
use Illuminate\Http\Request;

/**
 * @property FundPhysicalCardType $resource
 */
class SponsorFundPhysicalCardTypeResource extends BaseJsonResource
{
    public const array LOAD = [
        'physical_card_type.fund_configs',
        'physical_card_type.physical_cards',
    ];

    /**
     * @param string|null $append
     * @return array
     */
    public static function load(?string $append = null): array
    {
        return [
            ...parent::load($append),
            ...SponsorPhysicalCardTypeResource::load('physical_card_type'),
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
            'in_use' =>
                $cardType->physical_card_type->fund_configs->isNotEmpty() ||
                $cardType->physical_card_type->physical_cards->isNotEmpty(),
            'physical_card_type' => SponsorPhysicalCardTypeResource::create($cardType->physical_card_type),
        ];
    }
}
