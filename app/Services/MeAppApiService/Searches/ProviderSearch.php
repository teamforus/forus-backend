<?php

namespace App\Services\MeAppApiService\Searches;

use App\Models\Organization;
use App\Models\Product;
use App\Searches\BaseSearch;
use App\Searches\ProductSearch;
use Illuminate\Database\Eloquent\Builder;

class ProviderSearch extends BaseSearch
{
    /**
     * @param array $filters
     */
    public function __construct(array $filters)
    {
        parent::__construct($filters, Organization::query());
    }

    /**
     * @return Builder|Organization
     */
    public function query(): Builder|Organization
    {
        $products = (new ProductSearch([], Product::query()))
            ->queryWebshopSearch()
            ->where('show_on_webshop', true)
            ->reorder()
            ->select('organization_id');

        $query = parent::query();
        $query->whereIn('id', $products);
        $q = $this->getFilter('q');

        if (is_string($q) && $q !== '') {
            $query->where('name', 'like', "%$q%");
        }

        return $query->orderBy('name')->orderBy('id');
    }
}
