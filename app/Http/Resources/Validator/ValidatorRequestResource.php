<?php

namespace App\Http\Resources\Validator;

use App\Http\Resources\FileResource;
use App\Models\ValidatorRequest;
use App\Services\Forus\Record\Models\RecordType;
use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class ValidatorRequestResource
 * @property ValidatorRequest|array $resource
 * @package App\Http\Resources\Validator
 */
class ValidatorRequestResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function toArray($request)
    {
        $resource = $this->resource;

        if ($resource instanceof Collection) {
            return self::collection($resource)->toArray($request);
        }

        $recordRepo = app()->make('forus.services.record');
        $recordTypes = RecordType::query()->get()->keyBy('id');

        $bsn = collect(
            $recordRepo->recordsList($resource->identity_address, 'bsn')
        )->filter(function($record) use ($request) {
            return collect(
                    $record['validations']
                )->pluck('identity_address')->search(
                    auth_address()
                ) !== FALSE;
        })->first();

        return collect($this->resource)->only([
            'id', 'validator_id', 'record_validation_uid',
            'identity_address', 'record_id', 'state', 'validated_at',
            'created_at'
        ])->merge([
            'bsn' => $bsn ? $bsn['value'] : null,
            'record' => collect($this->resource->record)->only([
                'id', 'value', 'record_type_id'
            ])->merge([
                'name' => $recordTypes[$this->resource->record->record_type_id]->name,
                'key' => $recordTypes[$this->resource->record->record_type_id]->key
            ]),
            'files' => FileResource::collection($this->resource->files)
        ])->toArray();
    }
}
