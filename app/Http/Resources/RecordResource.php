<?php

namespace App\Http\Resources;

use App\Models\Record;

/**
 * @property-read Record $resource
 */
class RecordResource extends BaseJsonResource
{
    public static $wrap = null;

    public const LOAD = [
        'record_type.translations',
        'validations_approved.identity',
        'validations_approved.organization',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return (\Illuminate\Http\Resources\Json\AnonymousResourceCollection|bool|int|null|string)[]
     *
     * @psalm-return array{id: int, key: string, value: string, name: string, order: int, deleted: bool, record_category_id: int|null, validations: \Illuminate\Http\Resources\Json\AnonymousResourceCollection}
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
