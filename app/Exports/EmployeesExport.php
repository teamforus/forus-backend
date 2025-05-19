<?php

namespace App\Exports;

use App\Exports\Base\BaseFieldedExport;
use App\Models\Employee;
use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

class EmployeesExport extends BaseFieldedExport
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
     * FundsExport constructor.
     * @param Builder|Relation|Employee $builder
     * @param array $fields
     */
    public function __construct(Builder|Relation|Employee $builder, protected array $fields)
    {
        $this->data = $this->export($builder);
    }

    /**
     * @param Builder|Relation|Employee $builder
     * @return Collection
     */
    protected function export(Builder|Relation|Employee $builder): Collection
    {
        $search = $builder->with([
            'identity.emails',
            'identity.session_last_activity',
            'identity.primary_email',
            'identity.identity_2fa_active',
            'roles.translations',
            'organization',
            'logs' => fn (MorphMany $builder) => $builder->where([
                'event' => Employee::EVENT_UPDATED,
            ])->take(1)->latest(),
        ])->get();

        return $this->exportTransform($search);
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
     * @param Employee $employee
     * @return array
     */
    protected function getRow(Employee $employee): array
    {
        $employeeIsOwner = $employee->identity_address == $employee->organization->identity_address;
        $employeeLastUpdate = $employee->logs[0]?->created_at ?? $employee->updated_at;

        return array_merge([
            'email' => $employee->identity->email,
            'owner' => $employeeIsOwner ? 'ja' : 'nee',
            'branch_number' => $employee->office?->branch_number ?: '-',
            'branch_name' => $employee->office?->branch_name ?: '-',
            'branch_id' => $employee->office?->branch_id ?: '-',
            'is_2fa_configured' => $employee->identity->is2FAConfigured() ? 'ja' : 'nee',
            'created_at' => $employee->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $employeeLastUpdate?->format('Y-m-d H:i:s'),
            'last_activity' => $employee->identity->session_last_activity?->last_activity_at,
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
