<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Identities;

class StoreIdentityRequest extends UpdateIdentityRequest
{
    /**
     * Returns the validation rules for the current request.
     *
     * @return array
     */
    public function rules(): array
    {
        return parent::rules();
    }
}
