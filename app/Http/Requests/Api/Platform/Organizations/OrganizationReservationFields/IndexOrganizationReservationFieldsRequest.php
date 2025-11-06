<?php

namespace App\Http\Requests\Api\Platform\Organizations\OrganizationReservationFields;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\ReservationField;
use Illuminate\Support\Facades\Gate;

/**
 * @property-read Organization $organization
 */
class IndexOrganizationReservationFieldsRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('viewAny', [ReservationField::class, $this->organization]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'per_page' => $this->perPageRule(),
        ];
    }
}
