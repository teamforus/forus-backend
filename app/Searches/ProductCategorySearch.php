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
            $builder->where(function (ProductCategory|Builder $builder) {
                $productCategoriesBuilder = Product::searchQuery($this->hasFilter('used_type') ? [
                    'type' => $this->getfilter('used_type'),
                ] : [])->distinct()->select('product_category_id');

                $builder->whereIn('id', clone $productCategoriesBuilder);
                $builder->orWhereHas('descendants', function (Builder $builder) use ($productCategoriesBuilder) {
                    $builder->whereIn('id', clone $productCategoriesBuilder);
                });
            });
        }

        return $builder;
    }
}