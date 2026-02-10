<?php

namespace App\Searches;

use App\Models\PhysicalCardRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class PhysicalCardRequestSearch extends BaseSearch
{
    /**
     * @param array $filters
     * @param Builder|Relation|PhysicalCardRequest $builder
     */
    public function __construct(array $filters, Builder|Relation|PhysicalCardRequest $builder)
    {
        parent::__construct($filters, $builder);
    }

    /**
     * @return Builder|Relation|PhysicalCardRequest
     */
    public function query(): Builder|Relation|PhysicalCardRequest
    {
        /** @var Builder|Relation|PhysicalCardRequest $builder */
        $builder = parent::query();

        if ($fund_id = $this->getFilter('fund_id')) {
            $builder->whereHas('voucher', static function (Builder $query) use ($fund_id) {
                $query->where('fund_id', $fund_id);
            });
        }

        if ($date = $this->getFilter('date')) {
            $builder->whereDate('created_at', $date);
        }

        return $builder;
    }
}
