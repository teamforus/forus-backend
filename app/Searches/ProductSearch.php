<?php

namespace App\Searches;

use App\Models\Implementation;
use App\Models\Product;
use App\Scopes\Builders\OfficeQuery;
use App\Scopes\Builders\ProductQuery;
use App\Scopes\Builders\TrashedQuery;
use App\Services\MollieService\Models\MollieConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;

class ProductSearch extends BaseSearch
{
    /**
     * @param array $filters
     * @param Builder|Relation|Product $builder
     */
    public function __construct(array $filters, Builder|Relation|Product $builder)
    {
        parent::__construct($filters, $builder);
    }

    /**
     * @return Builder|Relation|Product
     */
    public function query(): Builder|Relation|Product
    {
        /** @var Builder|Relation|Product $builder */
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
            $builder->whereHas('logs_last_monitored_field_changed', function (Builder $builder) use ($updated_from) {
                $builder->where('created_at', '>=', Carbon::parse($updated_from)->startOfDay());
            });
        }

        if ($updated_to = $this->getFilter('updated_to')) {
            $builder->whereHas('logs_last_monitored_field_changed', function (Builder $builder) use ($updated_to) {
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
     * @return Builder|Relation|Product
     */
    public function queryWebshopSearch(): Builder|Relation|Product
    {
        /** @var Builder|Relation|Product $builder */
        $builder = parent::query();
        $activeFunds = Implementation::activeFundsQuery()->pluck('id')->toArray();

        $builder = ProductQuery::approvedForFundsFilter(
            ProductQuery::inStockAndActiveFilter($builder),
            $activeFunds
        );

        $builder->withCount('voucher_transactions');

        if ($product_category_id = $this->getFilter('product_category_id')) {
            $builder = ProductQuery::productCategoriesFilter($builder, $product_category_id);
        }

        if ($product_category_ids = $this->getFilter('product_category_ids')) {
            $builder = ProductQuery::productCategoriesFilter($builder, $product_category_ids);
        }

        if ($this->getFilter('fund_id')) {
            $builder = ProductQuery::approvedForFundsFilter($builder, $this->getFilter('fund_id'));
        }

        if ($this->getFilter('fund_ids')) {
            $builder = ProductQuery::approvedForFundsFilter($builder, $this->getFilter('fund_ids'));
        }

        if ($price_type = $this->getFilter('price_type')) {
            $builder = $builder->where('price_type', $price_type);
        }

        if (filter_bool($this->getFilter('unlimited_stock'))) {
            return ProductQuery::unlimitedStockFilter($builder, $this->getFilter('unlimited_stock'));
        }

        if ($organization_id = $this->getFilter('organization_id')) {
            $builder = $builder->where('organization_id', $organization_id);
        }

        $builder = ProductQuery::addPriceMinAndMaxColumn($builder);

        if ($q = $this->getFilter('q')) {
            ProductQuery::queryDeepFilter($builder, $q);
        }

        if ($this->getFilter('postcode') && $this->getFilter('distance')) {
            $geocodeService = resolve('geocode_api');
            $location = $geocodeService->getLocation($this->getFilter('postcode') . ', Netherlands');

            $builder->whereHas('organization.offices', static function (Builder $builder) use ($location) {
                OfficeQuery::whereDistance($builder, (int) $this->getFilter('distance'), [
                    'lat' => $location ? $location['lat'] : config('forus.office.default_lat'),
                    'lng' => $location ? $location['lng'] : config('forus.office.default_lng'),
                ]);
            });
        }

        if ($this->getFilter('qr')) {
            $this->whereQr($builder, $activeFunds);
        }

        if ($this->getFilter('reservation')) {
            ProductQuery::whereReservationEnabled($builder);
        }

        $this->wherePriceType($builder);

        if ($this->getFilter('extra_payment')) {
            $this->whereExtraPayment($builder, $activeFunds);
        }

        $orderBy = $this->getFilter('order_by', 'created_at');
        $orderBy = $orderBy === 'most_popular' ? 'voucher_transactions_count' : $orderBy;
        $orderDir = $this->getFilter('order_dir', 'desc');

        if ($orderBy === 'randomized') {
            return $builder->inRandomOrder();
        }

        return $builder
            ->orderBy($orderBy, $orderDir)
            ->orderBy('price_type')
            ->orderBy('price_discount')
            ->orderBy('created_at', 'desc');
    }

    /**
     * @param Builder|Relation|Product $builder
     * @param array $activeFunds
     * @return Builder|Relation|Product
     */
    protected function whereExtraPayment(Builder|Relation|Product $builder, array $activeFunds): Relation|Builder|Product
    {
        $builder->where(function (Builder $builder) use ($activeFunds) {
            ProductQuery::whereReservationEnabled($builder);

            $builder->where(function (Builder $builder) {
                $builder->where('reservation_extra_payments', Product::RESERVATION_EXTRA_PAYMENT_YES);

                $builder->orWhere(function (Builder $builder) {
                    $builder->where('reservation_extra_payments', Product::RESERVATION_EXTRA_PAYMENT_GLOBAL);
                    $builder->whereRelation('organization', 'reservation_allow_extra_payments', true);
                });
            });

            $builder->whereHas('organization.fund_providers_allowed_extra_payments', function (Builder $builder) use ($activeFunds) {
                $builder->whereIn('fund_id', $activeFunds);
            });

            $builder->whereHas('organization.mollie_connection', function (Builder $builder) use ($activeFunds) {
                $builder->where('onboarding_state', MollieConnection::ONBOARDING_STATE_COMPLETED);
            });
        });

        return $builder;
    }

    /**
     * @param Builder|Relation|Product $builder
     * @return Builder|Relation|Product
     */
    protected function wherePriceType(Builder|Relation|Product $builder): Relation|Builder|Product
    {
        $includeMap = [
            'free' => Product::PRICE_TYPE_FREE,
            'regular' => Product::PRICE_TYPE_REGULAR,
            'informational' => Product::PRICE_TYPE_INFORMATIONAL,
            'discount_fixed' => Product::PRICE_TYPE_DISCOUNT_FIXED,
            'discount_percentage' => Product::PRICE_TYPE_DISCOUNT_PERCENTAGE,
        ];

        $includeTypes = array_values(
            array_filter(
                $includeMap,
                fn ($type, $key) => !empty($this->getFilter($key)),
                ARRAY_FILTER_USE_BOTH
            )
        );

        if ($includeTypes) {
            $builder->whereIn('price_type', $includeTypes);
        }

        return $builder;
    }

    /**
     * @param Builder|Relation|Product $builder
     * @param array $activeFunds
     * @return Builder|Relation|Product
     */
    protected function whereQr(Builder|Relation|Product $builder, array $activeFunds): Relation|Builder|Product
    {
        return $builder->where(function (Builder $builder) use ($activeFunds) {
            $builder->where('qr_enabled', true);

            $builder->where(function (Builder $builder) use ($activeFunds) {
                $builder->where(function (Builder $builder) use ($activeFunds) {
                    $builder->whereHas('organization.fund_providers', function (Builder $builder) use ($activeFunds) {
                        $builder->whereIn('fund_id', $activeFunds);
                        $builder->where('allow_budget', true);
                    });

                    $builder->whereDoesntHave('fund_provider_products.fund_provider', function (Builder $builder) use ($activeFunds) {
                        $builder->whereIn('fund_id', $activeFunds);
                    });
                });

                $builder->orWhereHas('fund_provider_products', function (Builder $builder) use ($activeFunds) {
                    $builder->whereHas('fund_provider', fn (Builder $b) => $b->whereIn('fund_id', $activeFunds));
                    $builder->where('allow_scanning', true);
                });
            });
        });
    }

    /**
     * @param Builder|Relation|Product $builder
     * @param string $orderBy
     * @param string $orderDir
     * @return Builder|Relation|Product
     */
    protected function order(Builder|Relation|Product $builder, string $orderBy, string $orderDir): Builder|Relation|Product
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
