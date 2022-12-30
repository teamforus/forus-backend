<?php

namespace App\Exports;

use App\Models\Employee;
use App\Models\Role;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;

class EmployeesExport extends BaseFieldedExport
{
    use Exportable, RegistersEventListeners;

    protected Collection $data;
    protected bool $with_roles;

    /**
     * FundsExport constructor.
     * @param EloquentCollection $employees
     * @param bool $withRoles
     */
    public function __construct(EloquentCollection $employees, bool $withRoles = true)
    {
        $this->with_roles = $withRoles;
        $this->data = $this->exportTransform($employees);
    }

    /**
     * @param Collection $employees
     * @return Collection
     */
    protected function exportTransform(Collection $employees): Collection
    {
        if (!$this->with_roles) {
            return $employees->map(function(Employee $employee) {
                /** @var EventLog|null $lastUpdated */
                $lastUpdated = $employee->logs->first();

                return [
                    $this->trans("email") => $employee->identity->email,
                    $this->trans("created_at") => $employee->created_at,
                    $this->trans("updated_at") => $lastUpdated->created_at ?? $employee->created_at,
                ];
            });
        }

        $roles = Role::query()->get();

        return $employees->map(function(Employee $employee) use ($roles) {
            $employeeRoles = $employee->roles->pluck('id')->all();
            $arr = [
                $this->trans("email") => $employee->identity->email,
            ];

            /** @var Role $role */
            foreach ($roles as $role) {
                $arr[$role->name] = in_array($role->id, $employeeRoles) ? 'yes' : 'no';
            }

            /** @var EventLog|null $lastUpdated */
            $lastUpdated = $employee->logs->first();

            return array_merge($arr, [
                $this->trans("created_at") => $employee->created_at,
                $this->trans("updated_at") => $lastUpdated->created_at ?? $employee->created_at,
            ]);
        });
    }

    /**
     * @param string $key
     * @return string|null
     */
    protected function trans(string $key): ?string
    {
        return trans("export.employees.$key");
    }
}