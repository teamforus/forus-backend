<?php

namespace App\Http\Resources;

use App\Models\Employee;
use App\Models\Role;

/**
 * @property Employee $resource
 */
class EmployeeResource extends BaseJsonResource
{
    public const LOAD = [
        'organization',
        'roles.translations',
        'roles.permissions',
        'identity.primary_email',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $employee = $this->resource;

        return array_merge($employee->only('id', 'identity_address', 'organization_id'), [
            'roles' => RoleResource::collection($employee->roles),
            'permissions' => array_unique($employee->roles->reduce(function(array $list, Role $role) {
                return array_merge($list, $role->permissions->pluck('key')->toArray());
            }, [])),
            'email' => $employee->identity?->email,
            'organization' => $employee->organization->only('id', 'name'),
        ]);
    }
}
