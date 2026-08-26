<?php

namespace App\Http\Requests\Api\Platform\Organizations\ProductReservations;

use App\Exceptions\AuthorizationJsonException;
use App\Helpers\Locker;
use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\ProductReservation;
use App\Rules\ProviderMessageRecipientEmailRule;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
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
     * @throws AuthorizationJsonException
     * @throws InvalidArgumentException
     * @return bool
     */
    public function authorize(): bool
    {
        $key = "reservation_{$this->product_reservation->id}";

        $this->throttleWithKey('to_many_attempts', $this, 'accept_reservation', $key, 403);

        if (!Locker::make("accept_reservation.$key")->waitForUnlockAndLock()) {
            abort(429, 'To many requests, please try again later.');
        }

        return
            $this->isAuthenticated() &&
            Gate::allows('show', $this->organization) &&
            Gate::allows('acceptProvider', [$this->product_reservation, $this->organization]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'note' => 'nullable|string|max:255',
            'share_note_by_email' => [
                'nullable',
                'boolean',
                ...($this->filled('note') && in_array($this->input('share_note_by_email'), [true, 1, '1'], true) ? [
                    new ProviderMessageRecipientEmailRule($this->product_reservation),
                ] : []),
            ],
        ];
    }
}
