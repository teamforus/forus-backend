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
    public function __construct()
    {
        parent::__construct();
        $this->maxAttempts = Config::get('forus.throttles.accept_reservation.attempts');
        $this->decayMinutes = Config::get('forus.throttles.accept_reservation.decay') / 60;
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     * @throws AuthorizationJsonException
     * @throws InvalidArgumentException
     */
    public function authorize(): bool
    {
        $key = "reservation_{$this->product_reservation->id}";

        $this->throttleWithKey('to_many_attempts', $this, 'accept_reservation', $key, 403);

        if (!Locker::make("accept_reservation.$key")->waitForUnlockAndLock()) {
            abort(429, 'To many requests, please try again later.');
        }

        return $this->isAuthenticated() &&
            $this->organization->identityCan($this->identity(), 'scan_vouchers') &&
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
