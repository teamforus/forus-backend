<?php

namespace App\Http\Requests\Api\Platform\Organizations;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;

/**
 * Class UpdateOrganizationAcceptReservationsRequest
 * @property Organization $organization
 * @package App\Http\Requests\Api\Platform\Organizations
 */
class UpdateOrganizationAcceptReservationsRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->isAuthenticated() &&
            $this->organization->identity_address === $this->auth_address();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'reservations_auto_accept' => 'boolean',
        ];
    }
}
