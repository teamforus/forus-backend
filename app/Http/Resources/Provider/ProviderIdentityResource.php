<?php

namespace App\Http\Resources\Provider;

use App\Http\Resources\OrganizationResource;
use Illuminate\Http\Resources\Json\Resource;

class ProviderIdentityResource extends Resource
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
            'id', 'identity_address'
        ])->merge([
            'organization_id' => $this->resource->provider_id,
            'organization' => new OrganizationResource(
                $this->resource->organization
            ),
            'email' => collect($recordRepo->recordsList(
                $this->resource->identity_address
            ))->filter(function($record) {
                return $record['key']== 'primary_email';
            })->first()['value'],
        ])->toArray();
    }
}
