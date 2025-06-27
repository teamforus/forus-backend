<?php

namespace App\Searches;

use App\Models\Implementation;
use App\Models\Office;
use App\Scopes\Builders\FundProviderQuery;
use App\Scopes\Builders\OfficeQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class OfficeSearch extends BaseSearch
{
    /**
     * @param array $filters
     * @param Office|Relation|Builder $builder
     * @param bool $isEmployee
     */
    public function __construct(
        array $filters,
        Office|Relation|Builder $builder,
        public bool $isEmployee = false,
    ) {
        parent::__construct($filters, $builder);
    }

    /**
     * @return Relation|Builder
     */
    public function query(): Relation|Builder
    {
        /** @var Office|Builder $builder */
        $builder = parent::query();

        if ($this->hasFilter('organization_id')) {
            $builder->where('organization_id', $this->getFilter('organization_id'));
        }

        if ($this->getFilter('approved', false)) {
            $builder->whereHas('organization.fund_providers', static function (Builder $builder) {
                return FundProviderQuery::whereApprovedForFundsFilter(
                    $builder,
                    Implementation::activeFundsQuery()->pluck('id')->toArray(),
                );
            });
        }

        if ($this->getFilter('q')) {
            OfficeQuery::queryWebshopDeepFilter($builder, $this->getFilter('q'), $this->isEmployee);
        }

        return $builder;
    }
}
