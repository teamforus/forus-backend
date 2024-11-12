<?php

namespace App\Events\Products;

use App\Models\Product;

class ProductMonitoredFieldsUpdated extends BaseProductEvent
{
    /**
     * @param Product $product
     * @param array $updateFields
     * @param bool $updateBySponsor
     */
    public function __construct(
        Product $product,
        protected array $updateFields,
        protected bool $updateBySponsor,
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

    /**
     * @return bool
     */
    public function isUpdateBySponsor(): bool
    {
        return $this->updateBySponsor;
    }
}