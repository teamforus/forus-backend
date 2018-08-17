<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;

class ValidatorResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $recordRepo = app()->make('forus.services.record');

        return collect($this->resource)->only([
            'id', 'identity_address', 'organization_id'
        ])->merge([
            'email' => collect($recordRepo->recordsList(
                $this->resource->identity_address
            ))->filter(function($record) {
                return $record['key']== 'primary_email';
            })->first()['value'],
            'organization' => new OrganizationResource(
                $this->resource->organization
            )
        ])->toArray();
    }
}
