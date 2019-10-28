<?php

namespace App\Http\Resources;

use App\Models\Employee;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class EmployeeResource
 * @property Employee $resource
 * @package App\Http\Resources
 */
class EmployeeResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $employee = $this->resource;
        $recordRepo = app()->make('forus.services.record');

        return collect($employee)->only([
            'id', 'identity_address', 'organization_id'
        ])->merge([
            'organization' => $this->resource->organization->only([
                'id', 'name'
            ]),
            'roles' => RoleResource::collection($employee->roles),
            'email' => $recordRepo->primaryEmailByAddress(
                $employee->identity_address
            ),
        ]);
    }
}
