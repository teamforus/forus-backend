<?php


namespace App\Searches;


use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Exception;

/**
 * Class WebshopSearch
 * @package App\Searches
 */
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
     * @return Builder|null
     * @throws Exception
     */
    public function query(array|string $types): ?Builder
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
     * @return Builder
     * @throws Exception
     */
    protected function queryOfType(string $type, array $options): Builder
    {
        return match ($type) {
            "products" => $this->queryProducts($options),
            "providers" => $this->queryProviders($options),
            "funds" => $this->queryFunds($options),
            default => throw new Exception("Unknown query type"),
        };
    }

    /**
     * @param array $options
     * @return Builder
     */
    protected function queryProducts(array $options): Builder
    {
        return Product::search($options)->withoutTrashed();
    }

    /**
     * @param array $options
     * @return Builder
     */
    protected function queryProviders(array $options): Builder
    {
        return Implementation::searchProviders($options);
    }

    /**
     * @param array $options
     * @return Builder
     */
    protected function queryFunds(array $options): Builder
    {
        return Fund::search($options, Implementation::activeFundsQuery());
    }

    /**
     * @throws Exception
     */
    private function selectByType(Builder $query, string $type): Builder
    {
        $columns = ['id', 'name', 'created_at'];

        return match ($type) {
            "funds" => $query->select($columns)->selectRaw('"fund" as `item_type`'),
            "products" => $query->select($columns)->selectRaw('"product" as `item_type`'),
            "providers" => $query->select($columns)->selectRaw('"provider" as `item_type`'),
            default => throw new Exception("Unknown query type"),
        };
    }
}