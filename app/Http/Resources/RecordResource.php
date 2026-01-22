<?php

namespace App\Http\Resources;

use App\Models\Record;
use Illuminate\Http\Request;

/**
 * @property-read Record $resource
 */
class RecordResource extends BaseJsonResource
{
    public const array LOAD = [
        'record_type.translations',
    ];

    public const array LOAD_NESTED = [
        'validations_approved' => RecordValidationResource::class,
    ];
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
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
            'validations' => RecordValidationResource::collection($record->validations_approved),
        ];
    }
}
