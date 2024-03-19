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
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{reservations_auto_accept: 'boolean'}
     */
    public function rules(): array
    {
        return [
            'reservations_auto_accept' => 'boolean',
        ];
    }
}
