<?php

namespace App\Exports;

use App\Exports\Base\BaseExport;
use App\Models\Employee;
use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

class EmployeesExport extends BaseExport
{
    protected static string $transKey = 'employees';
    protected Collection $roleList;

    protected const string DYNAMIC_FIELD_ROLES = 'roles';
    protected const array DYNAMIC_FIELDS_KEYS = [self::DYNAMIC_FIELD_ROLES];

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
     * @param Builder|Relation|Employee $builder
     * @param array $fields
     */
    public function __construct(Builder|Relation|Employee $builder, array $fields)
    {
        $this->roleList = in_array(static::DYNAMIC_FIELD_ROLES, $fields, true)
            ? Role::with('translations')->get()
            : collect();

        parent::__construct($builder, $fields);
    }

    /**
     * @return array
     */
    protected function getBuilderWithArray(): array
    {
        return [
            'identity.session_last_activity',
            'identity.primary_email',
            'identity.identity_2fa_active',
            'roles.translations',
            'organization',
            'office',
            'logs' => fn (MorphMany $builder) => $builder->where([
                'event' => Employee::EVENT_UPDATED,
            ])->take(1)->latest(),
        ];
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
     * @param string $fieldKey
     * @return array
     */
    protected function getDynamicColumnDefinitionsFor(string $fieldKey): array
    {
        if ($fieldKey !== static::DYNAMIC_FIELD_ROLES || !$this->shouldExpandDynamicField($fieldKey)) {
            return [];
        }

        return $this->roleList->map(fn (Role $role) => [
            'key' => static::makeDynamicColumnKey($role->id, 'role'),
            'label' => $role->name,
        ])->values()->all();
    }

    /**
     * @param string $fieldKey
     * @param Model|Employee $model
     * @return array
     */
    protected function getDynamicRowValuesFor(string $fieldKey, Model|Employee $model): array
    {
        if ($fieldKey !== static::DYNAMIC_FIELD_ROLES || !$this->shouldExpandDynamicField($fieldKey)) {
            return [];
        }

        $employeeRoles = $model->roles->pluck('id')->toArray();

        return $this->roleList->mapWithKeys(fn (Role $role) => [
            static::makeDynamicColumnKey($role->id, 'role') => in_array($role->id, $employeeRoles) ? 'ja' : 'nee',
        ])->toArray();
    }
}
