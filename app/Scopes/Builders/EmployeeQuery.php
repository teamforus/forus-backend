<?php


namespace App\Scopes\Builders;

use App\Models\Fund;
use Illuminate\Database\Eloquent\Builder;

class EmployeeQuery
{
    /**
     * @param Builder $query
     * @param string|array $permissions
     * @return Builder
     */
    public static function whereHasPermissionFilter(
        Builder $query,
        $permissions
    ) {
        return $query->whereHas('roles.permissions', function(
            Builder $builder
        ) use ($permissions) {
            $builder->whereIn('permissions.key', (array) $permissions);
        });
    }
}