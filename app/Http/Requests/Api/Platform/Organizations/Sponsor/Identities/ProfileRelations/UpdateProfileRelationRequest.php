<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Identities\ProfileRelations;

class UpdateProfileRelationRequest extends StoreProfileRelationRequest
{
    /**
     * Returns the validation rules for the current request.
     *
     * @return array
     */
    public function rules(): array
    {
        return $this->baseRules(false);
    }
}
