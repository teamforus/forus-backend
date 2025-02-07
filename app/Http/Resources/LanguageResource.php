<?php

namespace App\Http\Resources;

use App\Models\Language;
use Illuminate\Http\Request;

/**
 * @property Language $resource
 */
class LanguageResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return $this->resource?->only([
            'id', 'locale', 'name', 'base',
        ]);
    }
}
