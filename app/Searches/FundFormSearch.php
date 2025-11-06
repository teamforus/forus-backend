<?php

namespace App\Searches;

use App\Models\FundForm;
use App\Scopes\Builders\FundQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class FundFormSearch extends BaseSearch
{
    /**
     * @param array $filters
     * @param Builder|Relation|FundForm $builder
     */
    public function __construct(array $filters, Builder|Relation|FundForm $builder)
    {
        parent::__construct($filters, $builder);
    }

    /**
     * @return Builder|Relation|FundForm
     */
    public function query(): Builder|Relation|FundForm
    {
        /** @var Builder|Relation|FundForm $builder */
        $builder = parent::query();

        if ($this->getFilter('fund_id')) {
            $builder->where('fund_id', $this->getFilter('fund_id'));
        }

        if ($q = $this->getFilter('q')) {
            $builder->where(function (Builder $builder) use ($q) {
                $builder->where('name', 'LIKE', "%$q%");
                $builder->orWhereRelation('fund', 'name', 'LIKE', "%$q%");
            });
        }

        if ($this->getFilter('implementation_id')) {
            $builder->whereRelation('fund.fund_config', 'implementation_id', $this->getFilter('implementation_id'));
        }

        if ($this->hasFilter('state')) {
            $builder->whereHas('fund', function (Builder $builder) {
                match ($this->getFilter('state')) {
                    'active' => FundQuery::whereActiveFilter($builder),
                    'archived' => FundQuery::whereNotActiveFilter($builder),
                };
            });
        }

        return $this->order($builder);
    }

    /**
     * @param Builder|Relation|FundForm $builder
     * @return Builder|Relation|FundForm
     */
    protected function order(Builder|Relation|FundForm $builder): Builder|Relation|FundForm
    {
        $orderBy = $this->getFilter('order_by', 'created_at');
        $orderDir = $this->getFilter('order_dir', 'asc');

        return $builder
            ->orderBy($orderBy, $orderDir)
            ->latest('created_at');
    }
}
