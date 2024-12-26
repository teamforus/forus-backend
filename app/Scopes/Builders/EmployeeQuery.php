<?php


namespace App\Scopes\Builders;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class EmployeeQuery
{
    /**
     * @param Builder|Relation|Employee $builder
     * @param string|array $permissions
     * @return Builder|Relation|Employee
     */
    public static function whereHasPermissionFilter(
        Builder|Relation|Employee $builder,
        string|array $permissions,
    ): Builder|Relation|Employee {
        return $builder->whereHas('roles.permissions', function(Builder $builder) use ($permissions) {
            $builder->whereIn('permissions.key', (array) $permissions);
        });
    }

    /**
     * @param Builder|Relation|Employee $builder
     * @param string|array $roles
     * @return Builder|Relation|Employee
     */
    public static function whereHasRoleFilter(
        Builder|Relation|Employee $builder,
        string|array $roles,
    ): Builder|Relation|Employee {
        return $builder->whereHas('roles', function(Builder $builder) use ($roles) {
            $builder->whereIn('roles.key', (array) $roles);
        });
    }

    /**
     * @param Builder|Relation|Employee $builder
     * @param string $q
     * @return Builder|Relation|Employee
     */
    public static function whereQueryFilter(
        Builder|Relation|Employee $builder,
        string $q,
    ): Builder|Relation|Employee {
        return $builder->whereRelation('identity.primary_email', 'email', 'LIKE', "%$q%");
    }
}