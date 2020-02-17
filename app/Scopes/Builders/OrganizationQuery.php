<?php


namespace App\Scopes\Builders;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;

class OrganizationQuery
{
    /**
     * @param Builder $builder
     * @param $identityAddress
     * @param $permissions
     * @return Organization|Builder|mixed
     */
    public static function whereHasPermissions(Builder $builder, $identityAddress, $permissions) {
        return $builder->where(function(
            Builder $builder
        ) use ($identityAddress, $permissions) {
            $builder->whereHas('employees', function(
                Builder $builder
            ) use ($identityAddress, $permissions) {
                $builder->where('employees.identity_address', $identityAddress);

                $builder->whereHas('roles.permissions', function(
                    Builder $builder
                ) use ($permissions) {
                    $builder->whereIn('permissions.key', (array) $permissions);
                });
            })->orWhere('organizations.identity_address', $identityAddress);
        });
    }
}