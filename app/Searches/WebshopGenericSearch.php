<?php

namespace App\Searches;

use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Product;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class WebshopGenericSearch
{
    protected array $filters;

    /**
     * WebshopSearch constructor.
     * @param array $filters
     */
    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    /**
     * @param array $filters
     * @noinspection PhpUnused
     */
    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
    }

    /**
     * @return array
     * @noinspection PhpUnused
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @param array|string $types
     * @throws Exception
     * @return Builder|Relation
     */
    public function query(array|string $types): Builder|Relation
    {
        $types = is_array($types) ? $types : func_get_args();
        $query = null;

        foreach ($types as $type) {
            $subQuery = $this->selectByType($this->queryOfType($type, $this->filters), $type);
            $query = $query ? $query->union($subQuery->getQuery()) : $subQuery;
        }

        return $query ?: Product::query()->whereRaw('0');
    }

    /**
     * @param string $type
     * @param array $options
     * @throws Exception
     * @return Builder
     */
    protected function queryOfType(string $type, array $options): Builder
    {
        return match ($type) {
            'products' => $this->queryProducts($options),
            'providers' => $this->queryProviders($options),
            'funds' => $this->queryFunds($options),
            default => throw new Exception('Unknown query type'),
        };
    }

    /**
     * @param array $options
     * @return Builder
     */
    protected function queryProducts(array $options): Builder
    {
        $search = new ProductSearch($options, Product::query());

        return $search->queryWebshopSearch()->where('show_on_webshop', true)->withoutTrashed();
    }

    /**
     * @param array $options
     * @return Builder|Relation
     */
    protected function queryProviders(array $options): Builder|Relation
    {
        return (new OrganizationSearch($options, Organization::query()))->queryProviders();
    }

    /**
     * @param array $options
     * @return Builder|Relation
     */
    protected function queryFunds(array $options): Builder|Relation
    {
        return (new FundSearch($options, Implementation::activeFundsQuery()))->query();
    }

    /**
     * @param Builder|Relation $query
     * @param string $type
     * @throws Exception
     * @return Builder|Relation
     */
    private function selectByType(Builder|Relation $query, string $type): Builder|Relation
    {
        $columns = ['id', 'name', 'created_at'];

        return match ($type) {
            'funds' => $query->select($columns)->selectRaw('"fund" as `item_type`'),
            'products' => $query->select($columns)->selectRaw('"product" as `item_type`'),
            'providers' => $query->select($columns)->selectRaw('"provider" as `item_type`'),
            default => throw new Exception('Unknown query type'),
        };
    }
}
