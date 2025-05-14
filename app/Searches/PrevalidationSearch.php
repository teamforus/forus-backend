<?php

namespace App\Searches;

use App\Models\Prevalidation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;

class PrevalidationSearch extends BaseSearch
{
    /**
     * @return Prevalidation|Relation|Builder
     */
    public function query(): Prevalidation|Relation|Builder
    {
        /** @var Prevalidation|Relation|Builder $builder */
        $builder = parent::query();

        if ($q = $this->getFilter('q')) {
            $builder->where(static function (Builder $query) use ($q) {
                $query->where('uid', 'like', "%$q%");

                $query->orWhereHas('prevalidation_records', function (Builder $builder) use ($q) {
                    $builder->where('value', 'like', "%$q%");
                });
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

        if ($this->hasFilter('exported')) {
            $builder->where('exported', '=', $this->getFilter('exported'));
        }

        return $this->order(
            $builder,
            $this->getFilter('order_by', 'created_at'),
            $this->getFilter('order_dir', 'desc'),
        );
    }

    /**
     * @param Prevalidation|Relation|Builder $builder
     * @param string $orderBy
     * @param string $orderDir
     * @return Prevalidation|Relation|Builder
     */
    protected function order(
        Prevalidation|Relation|Builder $builder,
        string $orderBy,
        string $orderDir,
    ): Prevalidation|Relation|Builder {
        return $builder->orderBy($orderBy, $orderDir);
    }
}
