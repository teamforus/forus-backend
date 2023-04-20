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
                $ids = Product::searchQuery($this->hasFilter('used_type') ? [
                    'type' => $this->getfilter('used_type'),
                ] : [])->distinct()->pluck('product_category_id')->toArray();

                $builder->whereIn('id', $ids);
                $builder->orWhereHas('descendants', fn (Builder $b) => $b->whereIn('id', $ids));
            });
        }

        return $builder;
    }
}