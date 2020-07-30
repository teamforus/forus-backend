<?php

namespace App\Http\Resources;

use App\Models\OrganizationValidator;
use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Support\Collection;

/**
 * Class OrganizationBasicResource
 * @property OrganizationValidator $resource
 * @package App\Http\Resources
 */
class OrganizationValidatorResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|Collection
     */
    public function toArray($request)
    {
        $validatorOrganization = $this->resource;

        return collect($validatorOrganization)->only([
            'id', 'organization_id', 'validator_organization_id',
        ])->merge([
            'organization' => new OrganizationBasicResource(
                $validatorOrganization->organization
            ),
            'validator_organization' => new OrganizationBasicResource(
                $validatorOrganization->validator_organization
            )
        ])->toArray();
    }
}
