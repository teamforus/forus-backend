<?php

namespace App\Searches;

use App\Models\PhysicalCardType;
use Illuminate\Database\Eloquent\Builder;

class PhysicalCardTypeSearch extends BaseSearch
{
    /**
     * @param array $filters
     * @param PhysicalCardType|Builder $builder
     */
    public function __construct(array $filters, PhysicalCardType|Builder $builder)
    {
        parent::__construct($filters, $builder);
    }

    /**
     * @return PhysicalCardType|Builder
     */
    public function query(): ?Builder
    {
        /** @var PhysicalCardType|Builder $builder */
        $builder = parent::query();

        if ($this->getFilter('q')) {
            $builder->where(function (Builder $query) {
                $query->where('name', 'like', '%' . $this->getFilter('q') . '%');
                $query->orWhere('description', 'like', '%' . $this->getFilter('q') . '%');
            });
        }

        if ($this->getFilter('fund_id')) {
            $builder->whereRelation('funds', 'funds.id', $this->getFilter('fund_id'));
        }

        return $this->order($builder);
    }

    /**
     * @param PhysicalCardType|Builder $builder
     * @return PhysicalCardType|Builder
     */
    protected function order(PhysicalCardType|Builder $builder): PhysicalCardType|Builder
    {
        $orderBy = $this->getFilter('order_by', 'created_at');
        $orderDir = $this->getFilter('order_dir', 'asc');

        return $builder
            ->orderBy($orderBy, $orderDir)
            ->oldest('created_at');
    }
}
