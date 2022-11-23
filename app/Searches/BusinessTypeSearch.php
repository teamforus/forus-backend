<?php

namespace App\Searches;

use App\Models\BusinessType;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;

class BusinessTypeSearch extends BaseSearch
{
    /**
     * ProductReservationsSearch constructor.
     * @param array $filters
     * @param Builder|null $builder
     */
    public function __construct(array $filters, Builder $builder = null)
    {
        parent::__construct($filters, $builder ?: BusinessType::query());
    }

    /**
     * @return Builder|null
     */
    public function query(): ?Builder
    {
        /** @var Builder $query */
        $builder = parent::query();

        if ($this->hasFilter('parent_id') && $parent_id = $this->getFilter('parent_id')) {
            $builder->where('parent_id', $parent_id == 'null' ? null : $parent_id);
        }

        if ($this->hasFilter('used') && $this->getFilter('used', false)) {
            $builder->where(function (BusinessType|Builder $builder) {
                $businessTypesBuilder = Organization::whereHas('supplied_funds_approved')->pluck('business_type_id');

                $builder->whereIn('id', clone $businessTypesBuilder);
                $builder->orWhereHas('descendants', function (Builder $builder) use ($businessTypesBuilder) {
                    $builder->whereIn('id', clone $businessTypesBuilder);
                });
            });
        }

        return $builder;
    }
}