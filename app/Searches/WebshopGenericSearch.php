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
    protected $filters;

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
     */
    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
    }

    /**
     * @return array
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
    public function query($types): ?Builder
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
        switch ($type) {
            case "products":
                return $this->queryProducts($options);
            case "providers":
                return $this->queryProviders($options);
            case "funds":
                return $this->queryFunds($options);
            default:
                throw new Exception("Unknown query type");
        }
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
        switch ($type) {
            case "funds":
                return $query->select([
                    'id', 'name', 'created_at'
                ])->selectRaw('"fund" as `item_type`');
            case "products":
                return $query->select([
                    'id', 'name', 'created_at'
                ])->selectRaw('"product" as `item_type`');
            case "providers":
                return $query->select([
                    'id', 'name', 'created_at'
                ])->selectRaw('"provider" as `item_type`');
            default:
                throw new Exception("Unknown query type");
        }
    }
}