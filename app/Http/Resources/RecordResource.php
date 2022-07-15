<?php

namespace App\Http\Resources;

use App\Models\Record;

/**
 * @property-read Record $resource
 */
class RecordResource extends BaseJsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $record = $this->resource;

        return [
            'id' => $record->id,
            'key' => $record->record_type->key,
            'value' => $record->value,
            'name' => $record->record_type->name,
            'order' => $record->order,
            'deleted' => !is_null($record->deleted_at),
            'record_category_id' => $record->record_category_id,
            'validations' => RecordValidationResource::collection($record->validations_approved)
        ];
    }
}
