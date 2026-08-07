<?php

namespace App\Searches;

use App\Models\FundProductLimit;
use App\Scopes\Builders\FundQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;

class FundProductLimitSearch extends BaseSearch
{
    /**
     * @return FundProductLimit|Relation|Builder
     */
    public function query(): FundProductLimit|Relation|Builder
    {
        /** @var FundProductLimit|Relation|Builder $builder */
        $builder = parent::query();

        if ($q = $this->getFilter('q')) {
            $builder->whereHas('fund', function (Builder $builder) use ($q) {
                FundQuery::whereQueryFilter($builder, $q);
            });
        }

        if ($fundId = $this->getFilter('fund_id')) {
            $builder->where('fund_id', $fundId);
        }

        if ($state = $this->getFilter('state')) {
            $builder->where('state', $state);
        }

        if ($this->getFilter('from') && $carbonFrom = Carbon::make($this->getFilter('from'))) {
            $builder->where('created_at', '>', $carbonFrom->startOfDay());
        }

        if ($this->getFilter('to') && $carbonTo = Carbon::make($this->getFilter('to'))) {
            $builder->where('created_at', '<', $carbonTo->endOfDay());
        }

        return $this->order(
            $builder,
            $this->getFilter('order_by', 'created_at'),
            $this->getFilter('order_dir', 'desc'),
        );
    }

    /**
     * @param FundProductLimit|Relation|Builder $builder
     * @param string $orderBy
     * @param string $orderDir
     * @return FundProductLimit|Relation|Builder
     */
    protected function order(
        FundProductLimit|Relation|Builder $builder,
        string $orderBy,
        string $orderDir,
    ): FundProductLimit|Relation|Builder {
        return $builder->orderBy($orderBy, $orderDir)->orderBy('id', 'desc');
    }
}
