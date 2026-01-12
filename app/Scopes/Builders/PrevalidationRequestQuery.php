<?php

namespace App\Scopes\Builders;

use App\Models\Permission;
use App\Models\PrevalidationRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class PrevalidationRequestQuery
{
    /**
     * @param Builder|Relation|PrevalidationRequest $builder
     * @param string $identityAddress
     * @return Builder|Relation|PrevalidationRequest
     */
    public static function whereVisibleToIdentity(
        Builder|Relation|PrevalidationRequest $builder,
        string $identityAddress,
    ): Builder|Relation|PrevalidationRequest {
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
