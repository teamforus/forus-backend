<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookmarkResource extends JsonResource
{
    /**
     * @return array
     */
    public static function load(): array
    {
        return [
            'bookmarkable',
        ];
    }

    /**
     * Transform the resource into an array.
     *
     * @param $request
     * @return array|null
     */
    public function toArray($request): ?array
    {
        if (is_null($bookmark = $this->resource)) {
            return null;
        }

        return $bookmark->only([
            'identity_address',
        ]);
    }
}
