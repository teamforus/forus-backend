<?php

namespace App\Http\Requests\Api\Platform\Organizations\Products;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\Product;

/**
 * @property Organization $organization
 */
abstract class BaseProductRequest extends BaseFormRequest
{
    /**
     * @return string[]
     */
    protected function reservationRules(): array
    {
        $options = implode(',', Product::RESERVATION_FIELDS_PRODUCT);
        $policies = implode(',', Product::RESERVATION_POLICIES);

        return [
            'reservation_enabled' => "nullable|boolean",
            'reservation_policy' => "nullable|in:$policies",
            'reservation_phone' => "nullable|in:$options",
            'reservation_address' => "nullable|in:$options",
            'reservation_birth_date' => "nullable|in:$options",
        ];
    }
}
    