<?php


namespace App\Searches;

use App\Models\Product;
use App\Scopes\Builders\ProductQuery;
use App\Scopes\Builders\TrashedQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ProductSearch extends BaseSearch
{
    /**
     * @param array $filters
     * @param Builder|null $builder
     */
    public function __construct(array $filters, Builder $builder = null)
    {
        parent::__construct($filters, $builder ?: Product::query());
    }

    /**
     * @return Product|Builder
     */
    public function query(): ?Builder
    {
        /** @var Product|Builder $builder */
        $builder = parent::query();
        $fundsIds = $this->getFilter('fund_ids', []);

        // filter by unlimited stock
        if ($this->hasFilter('unlimited_stock')) {
            ProductQuery::unlimitedStockFilter($builder, filter_bool($this->getFilter('unlimited_stock')));
        }

        // filter by string query
        if ($this->hasFilter('q') && !empty($q = $this->getFilter('q'))) {
            ProductQuery::queryDeepFilter($builder, $q);
        }

        if ($this->getFilter('state') === 'approved') {
            $builder = ProductQuery::approvedForFundsFilter($builder, $fundsIds);
        }

        if ($this->getFilter('state') === 'pending') {
            $builder = ProductQuery::notApprovedForFundsFilter($builder, $fundsIds);
        }

        // filter by string query
        if ($this->hasFilter('source') && !empty($source = $this->getFilter('source'))) {
            if ($source === 'sponsor') {
                $builder->whereNotNull('sponsor_organization_id');
            } elseif ($source === 'provider') {
                $builder->whereNull('sponsor_organization_id');
            } elseif ($source === 'archive') {
                $builder = TrashedQuery::onlyTrashed($builder);
            }
        }

        if ($this->hasFilter('price_min')) {
            $builder->where('price', '>=', $this->getFilter('price_min'));
        }

        if ($fundId = $this->getFilter('fund_id')) {
            $builder = ProductQuery::approvedForFundsFilter($builder, $fundId);
        }

        if ($this->hasFilter('price_max')) {
            $builder->where('price', '<=', $this->getFilter('price_max'));
        }

        if ($updated_from = $this->getFilter('updated_from')) {
            $builder->whereHas('logs_last_monitored_field_changed', function(Builder $builder) use ($updated_from) {
                $builder->where('created_at', '>=', Carbon::parse($updated_from)->startOfDay());
            });
        }

        if ($updated_to = $this->getFilter('updated_to')) {
            $builder->whereHas('logs_last_monitored_field_changed', function(Builder $builder) use ($updated_to) {
                $builder->where('created_at', '<=', Carbon::parse($updated_to)->startOfDay());
            });
        }

        if ($from = $this->getFilter('from')) {
            $builder->where('created_at', '>=', Carbon::parse($from)->startOfDay());
        }

        if ($to = $this->getFilter('to')) {
            $builder->where('created_at', '<=', Carbon::parse($to)->endOfDay());
        }

        if ($this->hasFilter('has_reservations')) {
            $has_reservations = $this->getFilter('has_reservations');

            if ($has_reservations) {
                $builder->whereHas('product_reservations');
            }

            if (!is_null($has_reservations) && !$has_reservations) {
                $builder->whereDoesntHave('product_reservations');
            }
        }

        return $this->order(
            $builder,
            $this->getFilter('order_by', 'created_at'),
            $this->getFilter('order_dir', 'desc'),
        );
    }

    /**
     * @param Product|Builder $builder Product|Builder
     * @param string $orderBy
     * @param string $orderDir
     * @return Product|Builder
     */
    protected function order(Product|Builder $builder, string $orderBy, string $orderDir): Product|Builder
    {
        if ($orderBy === 'stock_amount') {
            $builder = ProductQuery::stockAmountSubQuery($builder);
        }

        if ($orderBy === 'last_monitored_change_at') {
            $builder->whereHas('logs_monitored_field_changed');
            ProductQuery::addSelectLastMonitoredChangedDate($builder);
        }

        return Product::query()
            ->fromSub($builder, 'products')
            ->orderBy($orderBy, $orderDir)
            ->latest('created_at');
    }
}