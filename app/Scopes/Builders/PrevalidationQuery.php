<?php

namespace App\Scopes\Builders;

use App\Models\Permission;
use App\Models\Prevalidation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class PrevalidationQuery
{
    /**
     * @param Builder|Relation|Prevalidation $builder
     * @param string $identityAddress
     * @return Builder|Relation|Prevalidation
     */
    public static function whereVisibleToIdentity(
        Builder|Relation|Prevalidation $builder,
        string $identityAddress,
    ): Builder|Relation|Prevalidation {
        return $builder->where(function (Builder $builder) use ($identityAddress) {
            $builder->whereHas('organization', function (Builder $builder) use ($identityAddress) {
                OrganizationQuery::whereHasPermissions($builder, $identityAddress, Permission::MANAGE_ORGANIZATION);
            });

            $builder->orWhere(function (Builder $builder) use ($identityAddress) {
                $builder->whereRelation('employee', 'identity_address', $identityAddress);

                $builder->whereHas('organization', function (Builder $builder) use ($identityAddress) {
                    OrganizationQuery::whereHasPermissions($builder, $identityAddress, Permission::VALIDATE_RECORDS);
                });
            });
        });
    }
}
