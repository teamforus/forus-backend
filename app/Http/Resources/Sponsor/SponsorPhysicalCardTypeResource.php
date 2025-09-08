<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\MediaResource;
use App\Models\PhysicalCardType;
use Illuminate\Http\Request;

/**
 * @property PhysicalCardType $resource
 */
class SponsorPhysicalCardTypeResource extends BaseJsonResource
{
    /**
     * @var string[]
     */
    public const array LOAD = [
        'photo.presets',
    ];

    public const array LOAD_COUNT = [
        'funds',
        'physical_cards',
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
                'id', 'name', 'description', 'organization_id', 'code_prefix', 'code_blocks', 'code_block_size',
                'physical_cards_count', 'funds_count',
            ]),
            'photo' => new MediaResource($cardType->photo),
            'in_use' => $cardType->physical_cards_count > 0 || $cardType->funds_count > 0,
            ...$this->makeTimestamps($cardType->only([
                'created_at', 'updated_at',
            ])),
        ];
    }
}
