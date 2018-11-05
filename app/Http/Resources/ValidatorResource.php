<?php

namespace App\Http\Resources;

use App\Models\Validator;
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

        /** @var Validator $validator */
        $validator = $this->resource;

        return collect($validator)->only([
            'id', 'identity_address', 'organization_id'
        ])->merge([
            'email' => $recordRepo->primaryEmailByAddress(
                $validator->identity_address
            ),
            'organization' => new OrganizationResource(
                $validator->organization
            )
        ])->toArray();
    }
}
