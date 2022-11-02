<?php

namespace App\Searches;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Builder;

class ProductCategorySearch extends BaseSearch
{
    /**
     * ProductReservationsSearch constructor.
     * @param array $filters
     * @param Builder|null $builder
     */
    public function __construct(array $filters, Builder $builder = null)
    {
        parent::__construct($filters, $builder ?: ProductCategory::query());
    }

    /**
     * @return Builder|ProductCategory
     */
    public function query(): ?Builder
    {
        /** @var Builder|ProductCategory $builder */
        $builder = parent::query();

        if ($this->hasFilter('q') && $q = $this->getFilter('q')) {
            $builder->whereRelation('translations', 'name', 'LIKE', "%$q%");
        }

        if ($this->hasFilter('parent_id') && $parent_id = $this->getFilter('parent_id')) {
            $builder->where('parent_id', $parent_id == 'null' ? null : $parent_id);
        }

        if ($this->hasFilter('used') && $this->getFilter('used', false)) {
            $builder->whereHas('descendants', function (ProductCategory|Builder $builder) {
                $builder->whereIn('id', Product::searchQuery([
                    'type' => $this->getfilter('used_type'),
                ])->distinct()->select('product_category_id'));
            });
        }

        return $builder;
    }
}