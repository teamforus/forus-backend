<?php

namespace App\Searches;

use App\Models\ProductReservation;
use App\Scopes\Builders\ProductReservationQuery;
use Illuminate\Database\Eloquent\Builder;

class ProductReservationsSearch extends BaseSearch
{
    /**
     * ProductReservationsSearch constructor.
     * @param array $filters
     * @param Builder|null $builder
     */
    public function __construct(array $filters, Builder $builder = null)
    {
        parent::__construct($filters, $builder ?: ProductReservation::query());
    }

    /**
     * @return Builder|null
     */
    public function query(): ?Builder
    {
        $builder = parent::query();

        if ($this->hasFilter('q') && $this->getFilter('q')) {
            $builder = ProductReservationQuery::whereQueryFilter($builder, $this->getFilter('q'));
        }

        if ($this->hasFilter('state')) {
            if ($this->getFilter('state') === 'expired') {
                ProductReservationQuery::whereExpired($builder);
                $builder->where('state', ProductReservation::STATE_PENDING);
            } else {
                ProductReservationQuery::whereNotExpired($builder);
                $builder->where('state', $this->getFilter('state'));
            }
        }

        if ($this->hasFilter('organization_id')) {
            $builder->whereHas('product', function (Builder $builder) {
                $builder->where('organization_id', $this->getFilter('organization_id'));
            });
        }

        if ($this->hasFilter('from')) {
            $builder->whereDate('created_at', '>=', $this->getFilter('from'));
        }

        if ($this->hasFilter('to')) {
            $builder->whereDate('created_at', '<=', $this->getFilter('to'));
        }

        if ($this->hasFilter('fund_id')) {
            $builder->whereHas('voucher', function (Builder $builder) {
                $builder->where('fund_id', $this->getFilter('fund_id'));
            });
        }

        if ($this->hasFilter('product_id')) {
            $builder->where('product_id', $this->getFilter('product_id'));
        }

        if ($this->hasFilter('archived') && $this->getFilter('archived')) {
            $this->getFilter('is_webshop', false) ?
                ProductReservationQuery::whereArchived($builder) :
                $builder->where('archived', true);
        }

        if ($this->hasFilter('archived') && !$this->getFilter('archived')) {
            $this->getFilter('is_webshop', false) ?
                ProductReservationQuery::whereNotArchived($builder) :
                $builder->where('archived', false);
        }

        return $builder;
    }
}
