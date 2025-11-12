<?php

namespace App\Searches\Sponsor;

use App\Models\Household;
use App\Models\Identity;
use App\Scopes\Builders\IdentityQuery;
use App\Searches\BaseSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class HouseholdSearch extends BaseSearch
{
    /**
     * @param array $filters
     * @param Builder $builder
     */
    public function __construct(array $filters, Builder $builder)
    {
        parent::__construct($filters, $builder);
    }

    /**
     * @return Builder|Relation
     */
    public function query(): Builder|Relation
    {
        /** @var Builder|Household|Relation $builder */
        $builder = parent::query();

        if ($q = $this->getFilter('q')) {
            $builder->where(function (Builder $query) use ($q) {
                $query->where('city', 'LIKE', "%$q%");
                $query->orWhere('street', 'LIKE', "%$q%");
                $query->orWhere('postal_code', 'LIKE', "%$q%");
                $query->orWhere('neighborhood_name', 'LIKE', "%$q%");
                $query->orWhere('municipality_name', 'LIKE', "%$q%");
            });
        }

        if ($this->getFilter('living_arrangement')) {
            $builder->where('living_arrangement', $this->getFilter('living_arrangement'));
        }

        if ($this->getFilter('fund_id') && $this->getFilter('organization_id')) {
            $search = new IdentitiesSearch([
                'fund_id' => $this->getFilter('fund_id'),
                'organization_id' => $this->getFilter('organization_id'),
            ], IdentityQuery::relatedToOrganization(Identity::query(), $this->getFilter('organization_id')));

            $builder->whereHas('identity', function (Builder $query) use ($search) {
                $query->whereIn('id', $search->query()->select('id'));
            });
        }

        if ($this->getFilter('order_by')) {
            $builder->orderBy(
                $this->getFilter('order_by', 'created_at'),
                $this->getFilter('order_dir', 'desc')
            );
        }

        return $builder->orderBy('id');
    }
}
