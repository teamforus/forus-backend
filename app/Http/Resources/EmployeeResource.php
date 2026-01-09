<?php

namespace App\Http\Resources;

use App\Models\Employee;
use App\Models\Role;

/**
 * @property Employee $resource
 */
class EmployeeResource extends BaseJsonResource
{
    public const array LOAD = [
        'office',
        'organization',
        'roles.translations',
        'roles.permissions',
        'identity.primary_email',
        'identity.identity_2fa_active',
        'identity.session_last_activity',
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

        return array_merge($employee->only([
            'id', 'identity_address', 'organization_id', 'office_id',
        ]), [
            'roles' => RoleResource::collection($employee->roles),
            'permissions' => array_unique($employee->roles->reduce(function (array $list, Role $role) {
                return array_merge($list, $role->permissions->pluck('key')->toArray());
            }, [])),
            'email' => $employee->identity?->email,
            'organization' => $employee->organization->only('id', 'name'),
            'is_2fa_configured' => $employee->identity->is2FAConfigured(),
            'branch' => [
                'id' => $employee->office?->branch_id,
                'name' => $employee->office?->branch_name,
                'number' => $employee->office?->branch_number,
                'full_name' => $employee->office?->branch_full_name ?? '',
            ],
            ...static::makeTimestampsStatic([
                'created_at' => $employee->created_at,
                'last_activity_at' => $employee->identity->session_last_activity?->last_activity_at,
            ]),
        ]);
    }
}
