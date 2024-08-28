<?php


namespace App\Scopes\Builders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class EmployeeQuery
{
    /**
     * @param Builder|Relation $query
     * @param string|array $permissions
     * @return Builder|Relation
     */
    public static function whereHasPermissionFilter(
        Builder|Relation $query,
        string|array $permissions
    ): Builder|Relation {
        return $query->whereHas('roles.permissions', function(Builder $builder) use ($permissions) {
            $builder->whereIn('permissions.key', (array) $permissions);
        });
    }

    /**
     * @param Builder|Relation $query
     * @param string|array $roles
     * @return Builder|Relation
     */
    public static function whereHasRoleFilter(
        Builder|Relation $query,
        string|array $roles
    ): Builder|Relation {
        return $query->whereHas('roles', function(Builder $builder) use ($roles) {
            $builder->whereIn('roles.key', (array) $roles);
        });
    }

    /**
     * @param Builder|Relation $query
     * @param string $q
     * @return Builder|Relation
     */
    public static function whereQueryFilter(Relation|Builder $query, string $q): Relation|Builder
    {
        return $query->whereRelation('identity.primary_email', 'email', 'LIKE', "%$q%");
    }
}