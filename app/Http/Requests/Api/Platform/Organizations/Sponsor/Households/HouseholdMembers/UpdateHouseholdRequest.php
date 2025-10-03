<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Households\HouseholdMembers;

use App\Http\Requests\Api\Platform\Organizations\Sponsor\Households\StoreHouseholdRequest;

class UpdateHouseholdRequest extends StoreHouseholdRequest
{
    /**
     * Returns the validation rules for the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return $this->baseRules(false);
    }
}
