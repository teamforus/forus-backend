<?php

namespace App\Http\Requests\Api\Platform\Organizations;

use App\Models\Organization;

/**
 * @property-read Organization $organization
 */
class UpdateOrganizationReservationSettingsRequest extends BaseOrganizationRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return array_merge(
            $this->reservationRules(),
        );
    }
}
