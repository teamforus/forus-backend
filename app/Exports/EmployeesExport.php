<?php

namespace App\Exports;

use App\Models\Employee;
use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;

class EmployeesExport extends BaseFieldedExport
{
    use Exportable, RegistersEventListeners;

    protected Collection $data;
    protected bool $withRoles;
    protected Collection $roles;

    /**
     * FundsExport constructor.
     * @param Builder|Relation|Employee $builder
     * @param bool $withRoles
     */
    public function __construct(Builder|Relation|Employee $builder, bool $withRoles = true)
    {
        $this->roles = Role::get()->collect();
        $this->withRoles = $withRoles;

        $this->data = $this->exportTransform($builder->with([
            'roles',
            'organization',
            'logs' => fn (MorphMany $b) => $b->where([
                'event' => Employee::EVENT_UPDATED,
            ])->take(1)->latest(),
        ])->get());
    }

    /**
     * @param Collection $employees
     * @return Collection
     */
    protected function exportTransform(Collection $employees): Collection
    {
        return $employees->map(fn(Employee $employee) => $this->getEmployeeRow($employee));
    }

    /**
     * @param Employee $employee
     * @return array
     */
    protected function getEmployeeRow(Employee $employee): array
    {
        $employeeRoles = $this->withRoles ? $this->getRoles($employee) : [];
        $employeeIsOwner = $employee->identity_address == $employee->organization->identity_address;
        $employeeLastUpdate = $employee->logs[0]?->created_at ?? $employee->updated_at;

        return array_merge([
            trans("export.employees.email") => $employee->identity->email,
            trans("export.employees.owner") => $employeeIsOwner ? 'ja' : 'nee',
        ], $employeeRoles, [
            trans("export.employees.created_at") => $employee->created_at?->format('Y-m-d H:i:s'),
            trans("export.employees.updated_at") => $employeeLastUpdate?->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @param Employee $employee
     * @return array
     */
    protected function getRoles(Employee $employee): array
    {
        $employeeRoles = $employee->roles->pluck('id')->toArray();

        return $this->roles->mapWithKeys(fn (Role $role) => [
            $role->name => in_array($role->id, $employeeRoles) ? 'ja' : 'nee',
        ])->toArray();
    }
}