<?php

namespace App\Exports;

use App\Exports\Base\BaseExport;
use App\Models\Employee;
use App\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

class EmployeesExport extends BaseExport
{
    protected static string $transKey = 'employees';

    /**
     * @var array|string[]
     */
    protected static array $exportFields = [
        'email',
        'owner',
        'branch_number',
        'branch_name',
        'branch_id',
        'is_2fa_configured',
        'created_at',
        'updated_at',
        'last_activity',
        'roles',
    ];

    /**
     * @return array
     */
    protected function getBuilderWithArray(): array
    {
        return [
            'identity.emails',
            'identity.session_last_activity',
            'identity.primary_email',
            'identity.identity_2fa_active',
            'roles.translations',
            'organization',
            'logs' => fn (MorphMany $builder) => $builder->where([
                'event' => Employee::EVENT_UPDATED,
            ])->take(1)->latest(),
        ];
    }

    /**
     * @param Collection $data
     * @return Collection
     */
    protected function exportTransform(Collection $data): Collection
    {
        $roles = Role::with('translations')->get();
        $fieldLabels = array_pluck(static::getExportFields(), 'name', 'key');

        return $data->map(function (Employee $employee) use ($fieldLabels, $roles) {
            $row = array_only($this->getRow($employee), $this->fields);

            $row = array_reduce(array_keys($row), fn ($obj, $key) => array_merge($obj, [
                $fieldLabels[$key] => $row[$key],
            ]), []);

            $employeeRoles = in_array('roles', $this->fields) ? static::getRoles($employee, $roles) : [];

            return [...$row, ...$employeeRoles];
        })->values();
    }

    /**
     * @param Model|Employee $model
     * @return array
     */
    protected function getRow(Model|Employee $model): array
    {
        $employeeIsOwner = $model->identity_address == $model->organization->identity_address;
        $employeeLastUpdate = $model->logs[0]?->created_at ?? $model->updated_at;

        return array_merge([
            'email' => $model->identity->email,
            'owner' => $employeeIsOwner ? 'ja' : 'nee',
            'branch_number' => $model->office?->branch_number ?: '-',
            'branch_name' => $model->office?->branch_name ?: '-',
            'branch_id' => $model->office?->branch_id ?: '-',
            'is_2fa_configured' => $model->identity->is2FAConfigured() ? 'ja' : 'nee',
            'created_at' => $model->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $employeeLastUpdate?->format('Y-m-d H:i:s'),
            'last_activity' => $model->identity->session_last_activity?->last_activity_at,
        ]);
    }

    /**
     * @param Employee $employee
     * @param Collection $roles
     * @return array
     */
    protected static function getRoles(Employee $employee, Collection $roles): array
    {
        $employeeRoles = $employee->roles->pluck('id')->toArray();

        return $roles->mapWithKeys(fn (Role $role) => [
            $role->name => in_array($role->id, $employeeRoles) ? 'ja' : 'nee',
        ])->toArray();
    }
}
