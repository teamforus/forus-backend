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
        'identity.identity_2fa_active',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request  $request
     *
     * @return (\Illuminate\Http\Resources\Json\AnonymousResourceCollection|array|bool|mixed|null|string)[]
     *
     * @psalm-return array{roles: \Illuminate\Http\Resources\Json\AnonymousResourceCollection, permissions: array, email: null|string, organization: array, is_2fa_configured: bool, branch: array{id: null|string, name: null|string, number: int|null, full_name: string},...}
     */
    public function toArray($request): array
    {
        $employee = $this->resource;

        return array_merge($employee->only([
            'id', 'identity_address', 'organization_id', 'office_id',
        ]), [
            'roles' => RoleResource::collection($employee->roles),
            'permissions' => array_unique($employee->roles->reduce(function(array $list, Role $role) {
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
        ]);
    }
}
