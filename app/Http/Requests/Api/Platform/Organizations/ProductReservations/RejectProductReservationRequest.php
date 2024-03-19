<?php

namespace App\Http\Requests\Api\Platform\Organizations\ProductReservations;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\ProductReservation;

/**
 * @property Organization $organization
 * @property ProductReservation $product_reservation
 */
class RejectProductReservationRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     *
     * @psalm-return array<never, never>
     */
    public function rules(): array
    {
        return [];
    }
}
