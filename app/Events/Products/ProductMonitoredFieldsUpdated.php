<?php

namespace App\Events\Products;

use App\Models\Product;

class ProductMonitoredFieldsUpdated extends BaseProductEvent
{
    /**
     * @param Product $product
     * @param array $updateFields
     */
    public function __construct(
        Product $product,
        protected array $updateFields,
    ) {
        parent::__construct($product);
    }

    /**
     * @return mixed
     */
    public function getUpdateFields(): array
    {
        return $this->updateFields;
    }
}