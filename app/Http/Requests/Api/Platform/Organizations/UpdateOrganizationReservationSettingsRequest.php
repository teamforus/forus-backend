<?php

namespace App\Http\Requests\Api\Platform\Organizations;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;

/**
 * Class UpdateOrganizationReservationSettingsRequest
 * @property-read Organization $organization
 */
class UpdateOrganizationReservationSettingsRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->organization->identity_address === $this->auth_address();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            "reservation_phone" => "required|in:no,optional,required",
            "reservation_address" => "required|in:no,optional,required",
            "reservation_requester_birth_date" => "required|in:no,optional,required",
        ];
    }
}
