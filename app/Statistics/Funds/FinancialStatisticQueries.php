<?php


namespace App\Statistics\Funds;

use App\Models\BaseModel;
use App\Models\Office;
use App\Models\Organization;
use App\Models\ProductCategory;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\OrganizationQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

/**
 * Class FinancialStatistic
 * @package App\Statistics
 */
class FinancialStatisticQueries
{
    /**
     * @param Organization $sponsor
     * @param array $options
     * @return array
     */
    public function getFilterProductCategories(Organization $sponsor, array $options = []): array
    {
        /** @var Builder $query */
        $query = ProductCategory::whereNull('parent_id')->from('product_categories', 'categories');
        $query = $query->with('translations')->select('id');

        $transactionsQuery = $this->getFilterTransactionsQuery($sponsor, $options);

        $query->addSelect([
            'transactions' => $transactionsQuery->where(function(Builder $builder) {
                $builder->whereHas('product.product_category', function(Builder $builder) {
                    $builder->whereColumn('categories.id', 'root_id');
                });

                $builder->orWhereHas('voucher.product.product_category', function(Builder $builder) {
                    $builder->whereColumn('categories.id', 'root_id');
                });
            })->selectRaw('count(*)'),
        ])->orderByDesc('transactions');

        return $this->collectionOnly($query->get(), [
            'id', 'name', 'transactions'
        ]);
    }

    /**
     * @param Organization $sponsor
     * @param array $options
     * @return array
     */
    public function getFilterProviders(Organization $sponsor, array $options = []): array
    {
        $query = OrganizationQuery::whereIsProviderOrganization(Organization::query(), $sponsor)->select('id', 'name');
        $transactionsQuery = $this->getFilterTransactionsQuery($sponsor, $options);

        $query->addSelect([
            'transactions' => $transactionsQuery->where(function(Builder $builder) {
                $builder->whereColumn('voucher_transactions.organization_id', 'organizations.id');
            })->selectRaw('count(*)'),
        ])->orderByDesc('transactions');

        return $this->collectionOnly($query->get(), ['id', 'name', 'transactions']);
    }

    /**
     * @param Organization $sponsor
     * @param array $options
     * @return array
     */
    public function getFilterPostcodes(Organization $sponsor, array $options = []): array
    {
        $query = Office::query()->distinct();
        $transactionsQuery = $this->getFilterTransactionsQuery($sponsor, $options);

        $query->whereHas('organization', function(Builder $builder) use ($sponsor) {
            OrganizationQuery::whereIsProviderOrganization($builder, $sponsor);
        })->whereNotNull('postcode_number')->select('postcode_number');

        $query->addSelect([
            'transactions' => $transactionsQuery->whereHas('provider.offices', function(Builder $builder) {
                $builder->from('offices', 'offices2');
                $builder->whereColumn('offices.postcode_number', 'offices2.postcode_number');
            })->groupBy('postcode_number')->selectRaw('count(*)'),
        ])->orderByDesc('transactions');

        return $this->collectionOnly($query->get()->map(function(Office $office) {
            return (new BaseModel())->forceFill([
                'id' => $office->postcode_number,
                'name' => $office->postcode_number,
                'transactions' => $office->transactions ?? 0,
            ]);
        }), ['id', 'name', 'transactions']);
    }

    /**
     * @param Organization $sponsor
     * @param array $options
     * @return array
     */
    public function getFilterFunds(Organization $sponsor, array $options = []): array
    {
        $query = FundQuery::whereActiveOrClosedFilter($sponsor->funds()->getQuery(), false, false);
        $transactionsQuery = $this->getFilterTransactionsQuery($sponsor, $options);

        $query->select('id', 'name')->addSelect([
            'transactions' => $transactionsQuery->whereHas('voucher', function(Builder $builder) {
                $builder->whereColumn('vouchers.fund_id', 'funds.id');
            })->selectRaw('count(*)'),
        ])->orderByDesc('transactions');

        return $this->collectionOnly($query->get(), [
            'id', 'name', 'transactions',
        ]);
    }

    /**
     * @param Collection $collection
     * @param array $only
     * @return array
     */
    protected function collectionOnly(BaseCollection $collection, array $only): array {
        return $collection->map(function(BaseModel $model) use ($only) {
            return $model->only($only);
        })->values()->toArray();
    }

    /**
     * @param array $options
     * @param Organization $sponsor
     * @param Builder|null $query
     * @return Builder
     */
    public function getFilterTransactionsQuery(
        Organization $sponsor,
        array $options = [],
        Builder $query = null
    ): Builder {
        $productCategoryIds = array_get($options, 'product_category_ids');
        $providerIds = array_get($options, 'provider_ids');
        $postcodes = array_get($options, 'postcodes');
        $fundIds = array_get($options, 'fund_ids');
        $targets = array_get($options, 'targets', VoucherTransaction::TARGETS_OUTGOING);

        $dateFrom = array_get($options, 'date_from');
        $dateTo = array_get($options, 'date_to');

        $query = $query ?: VoucherTransaction::query();
        $query = $query->whereHas('voucher.fund', function(Builder $builder) use ($sponsor) {
            FundQuery::whereActiveOrClosedFilter($builder->where([
                'organization_id' => $sponsor->id
            ]), false, false);
        });

        // Filter by selected funds
        if ($fundIds) {
            $query->whereHas('voucher.fund', function(Builder $builder) use ($sponsor, $fundIds) {
                $builder->whereIn('funds.id', $sponsor->funds()->getQuery()->select('funds.id'));
                $builder->whereIn('id', $fundIds);
            });
        }

        // Filter by selected providers
        if ($providerIds || $postcodes) {
            $query->whereHas('provider', function(Builder $builder) use ($sponsor, $providerIds, $postcodes) {
                $providerIds && $builder->whereIn('id', $providerIds);
                $postcodes && OrganizationQuery::whereHasPostcodes($builder, $postcodes);
            });
        }

        // filter by category (include children categories)
        if ($productCategoryIds) {
            $query->where(function(Builder $builder) use ($productCategoryIds) {
                $builder->whereHas('product.product_category', function(Builder $builder) use ($productCategoryIds) {
                    $builder->whereIn('root_id', $productCategoryIds);
                });

                $builder->orWhereHas('voucher.product.product_category', function(Builder $builder) use ($productCategoryIds) {
                    $builder->whereIn('root_id', $productCategoryIds);
                });
            });
        }

        // filter by interval
        if ($dateFrom && $dateTo) {
            $query->whereBetween('voucher_transactions.created_at', [$dateFrom, $dateTo]);
        }

        $query->whereIn('target', is_array($targets) ? $targets : []);

        return $query;
    }
}