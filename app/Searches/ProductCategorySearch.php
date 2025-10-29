<?php

namespace App\Searches;

use App\Models\Implementation;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Scopes\Builders\ProductQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ProductCategorySearch extends BaseSearch
{
    /**
     * @param array $filters
     * @param Builder|Relation|ProductCategory $builder
     */
    public function __construct(array $filters, Builder|Relation|ProductCategory $builder)
    {
        parent::__construct($filters, $builder);
    }

    /**
     * @return Builder|Relation|ProductCategory
     */
    public function query(): Builder|Relation|ProductCategory
    {
        /** @var Builder|Relation|ProductCategory $builder */
        $builder = parent::query();

        if ($this->hasFilter('q') && $q = $this->getFilter('q')) {
            $builder->whereRelation('translations', 'name', 'LIKE', "%$q%");
        }

        if ($this->hasFilter('parent_id') && $parent_id = $this->getFilter('parent_id')) {
            $builder->where('parent_id', $parent_id == 'null' ? null : $parent_id);
        }

        if ($this->hasFilter('used') && $this->getFilter('used', false)) {
            $builder->where(function (ProductCategory|Builder $builder) {
                $builder = ProductQuery::approvedForFundsFilter(
                    ProductQuery::inStockAndActiveFilter(Product::query()),
                    Implementation::activeFundsQuery()->pluck('id')->toArray()
                );

                $ids = $builder->distinct()->pluck('product_category_id')->toArray();

                $builder->whereIn('id', $ids);
                $builder->orWhereHas('descendants', fn (Builder $b) => $b->whereIn('id', $ids));
            });
        }

        return $builder;
    }
}
