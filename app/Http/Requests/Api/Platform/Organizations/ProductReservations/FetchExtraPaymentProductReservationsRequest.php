<?php

namespace App\Http\Requests\Api\Platform\Organizations\ProductReservations;

use App\Exceptions\AuthorizationJsonException;
use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use Illuminate\Support\Facades\Config;

/**
 * @property Organization $organization
 */
class FetchExtraPaymentProductReservationsRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     * @throws AuthorizationJsonException
     */
    public function authorize(): bool
    {
        $this->maxAttempts = Config::get('forus.throttles.mollie.fetch_payments.attempts');
        $this->decayMinutes = Config::get('forus.throttles.mollie.fetch_payments.decay');

        $this->throttleWithKey('to_many_attempts', $this, 'reservation_extra_payment');

        return true;
    }
}
