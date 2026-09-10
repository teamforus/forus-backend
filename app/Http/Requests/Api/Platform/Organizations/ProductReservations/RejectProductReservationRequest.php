<?php

namespace App\Http\Requests\Api\Platform\Organizations\ProductReservations;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\ProductReservation;
use App\Rules\ProviderMessageRecipientEmailRule;
use Illuminate\Support\Facades\Gate;

/**
 * @property Organization $organization
 * @property ProductReservation $product_reservation
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
        return
            $this->isAuthenticated() &&
            Gate::allows('show', $this->organization) &&
            Gate::allows('rejectProvider', [$this->product_reservation, $this->organization]);
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
