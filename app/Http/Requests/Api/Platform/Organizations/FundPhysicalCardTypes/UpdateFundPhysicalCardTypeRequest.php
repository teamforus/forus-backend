<?php

namespace App\Http\Requests\Api\Platform\Organizations\FundPhysicalCardTypes;

use App\Http\Requests\Api\Platform\Organizations\Funds\BaseFundRequest;
use App\Models\Organization;

/**
 * @property Organization $organization
 */
class UpdateFundPhysicalCardTypeRequest extends BaseFundRequest
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
        return [
            'allow_physical_card_linking' => 'sometimes|boolean',
            'allow_physical_card_requests' => 'sometimes|boolean',
            'allow_physical_card_deactivation' => 'sometimes|boolean',
        ];
    }
}
