<?php

namespace App\Searches;

use App\Models\PhysicalCard;
use Illuminate\Database\Eloquent\Builder;

class PhysicalCardSearch extends BaseSearch
{
    /**
     * @param array $filters
     * @param PhysicalCard|Builder $builder
     */
    public function __construct(array $filters, PhysicalCard|Builder $builder)
    {
        parent::__construct($filters, $builder);
    }

    /**
     * @return PhysicalCard|Builder
     */
    public function query(): ?Builder
    {
        /** @var PhysicalCard|Builder $builder */
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
     * @param PhysicalCard|Builder $builder
     * @return PhysicalCard|Builder
     */
    protected function order(PhysicalCard|Builder $builder): PhysicalCard|Builder
    {
        $orderBy = $this->getFilter('order_by', 'created_at');
        $orderDir = $this->getFilter('order_dir', 'asc');

        return $builder
            ->orderBy($orderBy, $orderDir)
            ->oldest('created_at');
    }
}
