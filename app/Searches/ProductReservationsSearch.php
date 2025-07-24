<?php

namespace App\Searches;

use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\ProductReservationQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ProductReservationsSearch extends BaseSearch
{
    /**
     * ProductReservationsSearch constructor.
     * @param array $filters
     * @param Builder|Relation|null $builder
     */
    public function __construct(array $filters, Builder|Relation $builder = null)
    {
        parent::__construct($filters, $builder ?: ProductReservation::query());
    }

    /**
     * @return Builder|Relation
     */
    public function query(): Builder|Relation
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

        if ($this->hasFilter('voucher_id')) {
            $builder->where('voucher_id', $this->getFilter('voucher_id'));
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

        return $this->order($builder);
    }

    /**
     * @param Builder|Relation $builder
     * @return Builder|Relation
     */
    public function order(Builder|Relation $builder): Builder|Relation
    {
        $orderBy = $this->getFilter('order_by', 'created_at');
        $orderDir = $this->getFilter('order_dir', 'desc');

        if ($orderBy === 'product') {
            return $builder->orderBy(
                Product::query()
                    ->whereColumn('products.id', 'product_id')
                    ->select('name')
                    ->take(1),
                $orderDir
            );
        }

        if ($orderBy === 'transaction_id') {
            return $builder->orderBy(
                VoucherTransaction::query()
                    ->whereColumn('voucher_transactions.id', 'voucher_transaction_id')
                    ->select('id')
                    ->take(1),
                $orderDir
            );
        }

        if ($orderBy === 'transaction_state') {
            return $builder->orderBy(
                VoucherTransaction::query()
                    ->whereColumn('voucher_transactions.id', 'voucher_transaction_id')
                    ->select('state')
                    ->take(1),
                $orderDir
            );
        }

        if ($orderBy == 'provider') {
            return $builder->orderBy(
                Organization::query()
                    ->whereHas('products', function (Builder $builder) {
                        $builder->whereColumn('products.id', 'product_id');
                    })
                    ->select('name')
                    ->take(1),
                $orderDir
            );
        }

        if ($orderBy == 'customer') {
            return $builder->orderBy('first_name', $orderDir);
        }

        return $builder->orderBy($orderBy, $orderDir);
    }
}
