<?php

namespace App\Http\Resources\Validator;

use App\Services\Forus\Record\Models\RecordType;
use Illuminate\Http\Resources\Json\Resource;

class ValidatorRequestResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $resource = $this->resource;
        $recordRepo = app()->make('forus.services.record');
        $recordTypes = RecordType::getModel()->get()->keyBy('id');

        $bsn = collect(
            $recordRepo->recordsList($resource->identity_address, 'bsn')
        )->filter(function($record) use ($request) {
            return collect(
                    $record['validations']
                )->pluck('identity_address')->search(
                    auth()->user()->getAuthIdentifier()
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
            ])
        ])->toArray();
    }
}
