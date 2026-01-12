<?php

namespace App\Searches;

use App\Models\PrevalidationRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;

class PrevalidationRequestSearch extends BaseSearch
{
    /**
     * @return PrevalidationRequest|Relation|Builder
     */
    public function query(): PrevalidationRequest|Relation|Builder
    {
        /** @var PrevalidationRequest|Relation|Builder $builder */
        $builder = parent::query();

        if ($q = $this->getFilter('q')) {
            $builder->where('bsn', 'like', "%$q%");
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
     * @param PrevalidationRequest|Relation|Builder $builder
     * @param string $orderBy
     * @param string $orderDir
     * @return PrevalidationRequest|Relation|Builder
     */
    protected function order(
        PrevalidationRequest|Relation|Builder $builder,
        string $orderBy,
        string $orderDir,
    ): PrevalidationRequest|Relation|Builder {
        return $builder->orderBy($orderBy, $orderDir)->orderBy('id', 'desc');
    }
}
