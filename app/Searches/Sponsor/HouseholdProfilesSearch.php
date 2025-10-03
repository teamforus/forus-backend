<?php

namespace App\Searches\Sponsor;

use App\Models\HouseholdProfile;
use App\Models\Identity;
use App\Scopes\Builders\IdentityQuery;
use App\Searches\BaseSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class HouseholdProfilesSearch extends BaseSearch
{
    /**
     * @return Builder|Relation
     */
    public function query(): Builder|Relation
    {
        /** @var Builder|HouseholdProfile $builder */
        $builder = parent::query();

        if ($this->getFilter('q') && $this->getFilter('organization_id')) {
            $search = new IdentitiesSearch([
                'q' => $this->getFilter('q'),
                'organization_id' => $this->getFilter('organization_id'),
            ], IdentityQuery::relatedToOrganization(Identity::query(), $this->getFilter('organization_id')));

            $builder->whereHas('profile.identity', function (Builder $query) use ($search) {
                $query->whereIn('id', $search->query()->select('id'));
            });
        }

        return $builder
            ->orderBy($this->getFilter('sort_by', 'created_at'), $this->getFilter('sort_dir', 'desc'))
            ->orderBy('id');
    }
}
