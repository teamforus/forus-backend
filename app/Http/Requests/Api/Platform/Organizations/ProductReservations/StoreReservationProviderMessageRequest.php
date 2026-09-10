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
class StoreReservationProviderMessageRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('storeProviderMessage', [$this->product_reservation, $this->organization]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'message' => [
                'required',
                'string',
                'max:2000',
                new ProviderMessageRecipientEmailRule($this->product_reservation),
            ],
        ];
    }
}
