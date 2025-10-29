<?php

namespace App\Searches;

use App\Models\PhysicalCard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class PhysicalCardSearch extends BaseSearch
{
    /**
     * @param array $filters
     * @param Builder|Relation|PhysicalCard $builder
     */
    public function __construct(array $filters, Builder|Relation|PhysicalCard $builder)
    {
        parent::__construct($filters, $builder);
    }

    /**
     * @return Builder|Relation|PhysicalCard
     */
    public function query(): Builder|Relation|PhysicalCard
    {
        /** @var Builder|Relation|PhysicalCard $builder */
        $builder = parent::query();

        if ($this->getFilter('q')) {
            $builder->where(function (Builder $query) {
                $query->where('code', 'like', '%' . $this->getFilter('q') . '%');
            });
        }

        if ($this->getFilter('physical_card_type_id')) {
            $builder->where('physical_card_type_id', $this->getFilter('physical_card_type_id'));
        }

        if ($this->getFilter('fund_id')) {
            $builder->whereRelation('voucher.fund', 'id', $this->getFilter('fund_id'));
        }

        return $this->order($builder);
    }

    /**
     * @param Builder|Relation|PhysicalCard $builder
     * @return Builder|Relation|PhysicalCard
     */
    protected function order(Builder|Relation|PhysicalCard $builder): Builder|Relation|PhysicalCard
    {
        $orderBy = $this->getFilter('order_by', 'created_at');
        $orderDir = $this->getFilter('order_dir', 'asc');

        return $builder
            ->orderBy($orderBy, $orderDir)
            ->oldest('created_at');
    }
}
