<?php

namespace App\Scopes\Builders;

use App\Models\Identity;
use App\Models\Organization;
use App\Models\Profile;
use App\Models\ProfileRelation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ProfileRelationQuery
{
    /**
     * @param Builder|Relation|ProfileRelation $builder
     * @param Identity $identity
     * @param Organization $organization
     * @return Builder|Relation|ProfileRelation
     */
    public static function filterIdentityRelations(
        Builder|Relation|ProfileRelation $builder,
        Identity $identity,
        Organization $organization,
    ): Builder|Relation|ProfileRelation {
        return $builder->where(function (Builder|ProfileRelation $query) use ($organization, $identity) {
            $query->whereRelation('profile', function (Builder|Profile $query) use ($organization, $identity) {
                $query->where('organization_id', $organization->id);
                $query->where('identity_id', $identity->id);
            });

            $query->orWhereHas('related_profile', function (Builder|Profile $query) use ($organization, $identity) {
                $query->where('organization_id', $organization->id);
                $query->where('identity_id', $identity->id);
            });
        });
    }
}
