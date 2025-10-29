<?php

namespace App\Searches;

use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Product;
use App\Scopes\Builders\FundProviderQuery;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\OfficeQuery;
use App\Scopes\Builders\OrganizationQuery;
use App\Scopes\Builders\ProductQuery;
use App\Statistics\Funds\FinancialStatisticQueries;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class OrganizationSearch extends BaseSearch
{
    /**
     * @param array $filters
     * @param Builder|Relation|Organization $builder
     */
    public function __construct(array $filters, Builder|Relation|Organization $builder)
    {
        parent::__construct($filters, $builder);
    }

    /**
     * @return Builder|Relation|Organization
     */
    public function query(): Builder|Relation|Organization
    {
        /** @var Builder|Relation|Organization $builder */
        $builder = parent::query();

        if ($this->getFilter('is_sponsor')) {
            $builder->where('is_sponsor', true);
        }

        if ($this->getFilter('is_provider')) {
            $builder->where('is_provider', true);
        }

        if ($this->getFilter('is_validator')) {
            $builder->where('is_validator', true);
        }

        if ($this->getFilter('has_reservations') && $this->getFilter('auth_address')) {
            $builder->whereHas('products.product_reservations', function (Builder $builder) {
                $builder->whereHas('voucher', function (Builder $builder) {
                    $builder->whereRelation('identity', 'address', $this->getFilter('auth_address'));
                });
            });
        }

        if ($this->getFilter('q')) {
            $builder = OrganizationQuery::queryFilterPublic($builder, $this->getFilter('q'));
        }

        $builder = match ($this->getFilter('type')) {
            'sponsor' => $this->whereHasFunds($builder, $this->getFilter('implementation_id')),
            'provider' => $this->whereHasProducts($builder, $this->getFilter('implementation_id')),
            default => $builder->where(function (Builder $builder) {
                if ($this->getFilter('auth_address')) {
                    OrganizationQuery::whereIsEmployee($builder, $this->getFilter('auth_address'));
                } else {
                    $builder->whereIn('id', []);
                }
            }),
        };

        return $builder->orderBy(
            $this->getFilter('order_by', 'created_at'),
            $this->getFilter('order_dir', 'asc'),
        );
    }

    /**
     * @return Builder|Relation|Organization
     */
    public function queryProviders(): Builder|Relation|Organization
    {
        /** @var Builder|Relation|Organization $builder */
        $builder = parent::query();

        $builder->whereHas('fund_providers', static function (Builder $builder) {
            $builder->whereIn('fund_id', Implementation::activeFundsQuery()->select('id'));
            FundProviderQuery::whereApproved($builder);
        });

        if ($business_type_id = $this->getFilter('business_type_id')) {
            $builder->where('business_type_id', $business_type_id);
        }

        if ($product_category_id = $this->getFilter('product_category_id')) {
            $search = new ProductSearch(compact('product_category_id'), Product::query());

            $builder->whereHas('products', function (Builder $builder) use ($search) {
                $builder->whereIn('id', $search->queryWebshopSearch()->select('id'));
            });
        }

        if ($product_category_ids = $this->getFilter('product_category_ids')) {
            $search = new ProductSearch(compact('product_category_ids'), Product::query());

            $builder->whereHas('products', function (Builder $builder) use ($search) {
                $builder->whereIn('id', $search->queryWebshopSearch()->select('id'));
            });
        }

        if ($organization_id = $this->getFilter('organization_id')) {
            $builder->where('id', $organization_id);
        }

        if ($fund_id = $this->getFilter('fund_id')) {
            $builder->whereRelation('supplied_funds', 'funds.id', $fund_id);
        }

        if ($fund_ids = $this->getFilter('fund_ids')) {
            $builder->whereRelation('supplied_funds', fn (Builder $b) => $b->whereIn('funds.id', $fund_ids));
        }

        if ($this->getFilter('q')) {
            $builder = OrganizationQuery::queryFilterProviders($builder, $this->getFilter('q'));
        }

        if ($this->getFilter('postcode') && $this->getFilter('distance')) {
            $geocodeService = resolve('geocode_api');
            $location = $geocodeService->getLocation($this->getFilter('postcode') . ', Netherlands');

            $builder->whereHas('offices', static function (Builder $builder) use ($location) {
                OfficeQuery::whereDistance($builder, (int) $this->getFilter('distance'), [
                    'lat' => $location ? $location['lat'] : config('forus.office.default_lat'),
                    'lng' => $location ? $location['lng'] : config('forus.office.default_lng'),
                ]);
            });
        }

        return $builder->orderBy(
            $this->getFilter('order_by', 'created_at'),
            $this->getFilter('order_dir', 'desc'),
        );
    }

    /**
     * @param Organization $sponsor
     * @return Builder|Relation|Organization
     */
    public function searchProviderOrganizations(Organization $sponsor): Builder|Relation|Organization
    {
        /** @var Builder|Relation|Organization $builder */
        $builder = parent::query();

        /** @var Carbon|null $dateFrom */
        $dateFrom = $this->getFilter('date_from');
        /** @var Carbon|null $dateTo */
        $dateTo = $this->getFilter('date_to');

        $builder = OrganizationQuery::whereIsProviderOrganization($builder, $sponsor);

        if ($this->getFilter('provider_ids')) {
            $builder->whereIn('id', $this->getFilter('provider_ids'));
        }

        if ($postcodes = $this->getFilter('postcodes')) {
            $builder->whereHas('offices', static function (Builder $builder) use ($postcodes) {
                $builder->whereIn('postcode_number', (array) $postcodes);
            });
        }

        if ($this->getFilter('business_type_ids')) {
            $builder->whereIn('business_type_id', $this->getFilter('business_type_ids'));
        }

        if ($dateFrom && $dateTo) {
            $builder->whereHas('voucher_transactions', function (Builder $builder) use ($dateFrom, $dateTo) {
                $builder->where('created_at', '>=', $dateFrom->clone()->startOfDay());
                $builder->where('created_at', '<=', $dateTo->clone()->endOfDay());
            });
        }

        $queryTransactions = (new FinancialStatisticQueries())->getFilterTransactionsQuery($sponsor, $this->getFilters());
        $queryTransactions->whereColumn('organization_id', 'organizations.id');

        $builder->addSelect([
            'total_spent' => (clone $queryTransactions)->selectRaw('sum(`amount`)'),
            'highest_transaction' => (clone $queryTransactions)->selectRaw('max(`amount`)'),
            'nr_transactions' => (clone $queryTransactions)->selectRaw('count(`id`)'),
        ])->orderByDesc('total_spent');

        return $builder;
    }

    /**
     * @param Builder|Relation|Organization $builder
     * @param int $implementationId
     * @return Builder|Relation|Organization
     */
    protected function whereHasFunds(
        Builder|Relation|Organization $builder,
        int $implementationId,
    ): Builder|Relation|Organization {
        return $builder->whereHas('funds', function (Builder $builder) use ($implementationId) {
            $builder = FundQuery::whereIsConfiguredByForus(FundQuery::whereActiveFilter($builder));
            $builder->whereRelation('fund_config', 'implementation_id', $implementationId);
        });
    }

    /**
     * @param Builder|Relation|Organization $builder
     * @param int $implementationId
     * @return Builder|Relation|Organization
     */
    protected function whereHasProducts(
        Builder|Relation|Organization $builder,
        int $implementationId,
    ): Builder|Relation|Organization {
        return $builder->whereHas('products', function (Builder $builder) use ($implementationId) {
            $activeFunds = Implementation::find($implementationId)->funds();
            $activeFunds = FundQuery::whereIsInternalConfiguredAndActive($activeFunds);

            // only in stock and not expired
            $builder = ProductQuery::inStockAndActiveFilter($builder->select('id'));

            // only approved by at least one sponsor
            return ProductQuery::approvedForFundsFilter($builder, $activeFunds->pluck('funds.id')->toArray());
        });
    }
}
