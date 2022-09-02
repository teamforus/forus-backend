<?php

namespace App\Http\Resources;

use App\Models\OrganizationValidator;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class OrganizationBasicResource
 * @property OrganizationValidator $resource
 * @package App\Http\Resources
 */
class OrganizationValidatorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return array_merge($this->resource->only([
            'id', 'organization_id', 'validator_organization_id',
        ]), [
            'organization' => new OrganizationBasicResource($this->resource->organization),
            'validator_organization' => new OrganizationBasicResource($this->resource->validator_organization)
        ]);
    }
}
