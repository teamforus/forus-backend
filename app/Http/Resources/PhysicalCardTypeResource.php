<?php

namespace App\Http\Resources;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\MediaResource;
use App\Models\PhysicalCardType;
use Illuminate\Http\Request;

/**
 * @property PhysicalCardType $resource
 */
class PhysicalCardTypeResource extends BaseJsonResource
{
    /**
     * @var string[]
     */
    public const array LOAD = [
        'photo.presets',
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
                'id', 'name', 'description', 'code_prefix', 'code_blocks', 'code_block_size',
            ]),
            'photo' => new MediaResource($cardType->photo),
        ];
    }
}
