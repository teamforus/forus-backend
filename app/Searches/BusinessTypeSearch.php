<?php

namespace App\Searches;

use App\Models\BusinessType;
use App\Models\Implementation;
use App\Scopes\Builders\FundProviderQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class BusinessTypeSearch extends BaseSearch
{
    /**
     * @param array $filters
     * @param Builder|Relation|BusinessType $builder
     */
    public function __construct(array $filters, Builder|Relation|BusinessType $builder)
    {
        parent::__construct($filters, $builder);
    }

    /**
     * @return Builder|Relation|BusinessType
     */
    public function query(): Builder|Relation|BusinessType
    {
        /** @var Builder|Relation|BusinessType $builder */
        $builder = parent::query();

        if ($this->getFilter('used', false)) {
            $builder->whereHas('organizations.fund_providers', function (Builder $builder) {
                $builder->whereIn('fund_id', Implementation::activeFundsQuery()->select('id'));
                FundProviderQuery::whereApproved($builder);
            });
        }

        return $builder;
    }
}
