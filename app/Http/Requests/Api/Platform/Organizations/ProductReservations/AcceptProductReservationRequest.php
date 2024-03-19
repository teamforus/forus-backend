<?php

namespace App\Http\Requests\Api\Platform\Organizations\ProductReservations;

use App\Exceptions\AuthorizationJsonException;
use App\Helpers\Locker;
use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\ProductReservation;
use Illuminate\Support\Facades\Config;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * @property Organization $organization
 * @property ProductReservation $product_reservation
 */
class AcceptProductReservationRequest extends BaseFormRequest
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
