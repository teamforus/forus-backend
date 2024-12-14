<?php

namespace App\Http\Resources;

use App\Models\PrevalidationRecord;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;

/**
 * @property PrevalidationRecord $resource
 */
class PrevalidationRecordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return array_merge($this->resource->only([
            'id', 'record_type_id', 'value',
        ]), [
            'key' => $this->resource->record_type->key,
            'name' => $this->resource->record_type->name,
        ]);
    }
}
