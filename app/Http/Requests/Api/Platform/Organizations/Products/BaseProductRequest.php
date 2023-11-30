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

        $extraPaymentOptions = $this->organization->canUseExtraPaymentsAsProvider() ?
            implode(',', Product::RESERVATION_EXTRA_PAYMENT_OPTIONS) :
            '';

        return [
            'reservation_enabled' => "nullable|boolean",
            'reservation_fields' => "nullable|boolean",
            'reservation_policy' => "nullable|in:$policies",
            'reservation_phone' => "nullable|in:$options",
            'reservation_address' => "nullable|in:$options",
            'reservation_birth_date' => "nullable|in:$options",
            'reservation_extra_payments' => "nullable|in:$extraPaymentOptions",
        ];
    }
}
    