<?php

namespace App\Http\Requests\Api\Platform\Organizations;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\Product;

/**
 * @property-read Organization $organization
 */
abstract class BaseOrganizationRequest extends BaseFormRequest
{
    /**
     * @return string[]
     */
    protected function reservationRules(): array
    {
        $options = implode(',', Product::RESERVATION_FIELDS_ORGANIZATION);

        return [
            'reservation_phone' => "nullable|in:$options",
            'reservation_address' => "nullable|in:$options",
            'reservation_birth_date' => "nullable|in:$options",
            'reservation_allow_extra_payments' => 'nullable|boolean',
        ];
    }
}
