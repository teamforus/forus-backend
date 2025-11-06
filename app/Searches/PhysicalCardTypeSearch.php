<?php

namespace App\Searches;

use App\Models\PhysicalCardType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class PhysicalCardTypeSearch extends BaseSearch
{
    /**
     * @param array $filters
     * @param Builder|Relation|PhysicalCardType $builder
     */
    public function __construct(array $filters, Builder|Relation|PhysicalCardType $builder)
    {
        parent::__construct($filters, $builder);
    }

    /**
     * @return Builder|Relation|PhysicalCardType
     */
    public function query(): Builder|Relation|PhysicalCardType
    {
        /** @var Builder|Relation|PhysicalCardType $builder */
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
     * @param Builder|Relation|PhysicalCardType $builder
     * @return Builder|Relation|PhysicalCardType
     */
    protected function order(Builder|Relation|PhysicalCardType $builder): Builder|Relation|PhysicalCardType
    {
        $orderBy = $this->getFilter('order_by', 'created_at');
        $orderDir = $this->getFilter('order_dir', 'asc');

        return $builder
            ->orderBy($orderBy, $orderDir)
            ->oldest('created_at');
    }
}
