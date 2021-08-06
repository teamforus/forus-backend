<?php

namespace App\Http\Requests\Api\Platform\Organizations\ProductReservations;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\ProductReservation;

/**
 * Class RejectProductReservationRequest
 * @property Organization $organization
 * @property ProductReservation $product_reservation
 * @package App\Http\Requests\Api\Platform\Organizations\ProductReservations
 */
class RejectProductReservationRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->isAuthenticated() &&
            $this->organization->identityCan('scan_vouchers') &&
            $this->organization->id === $this->product_reservation->product->organization_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [];
    }
}
