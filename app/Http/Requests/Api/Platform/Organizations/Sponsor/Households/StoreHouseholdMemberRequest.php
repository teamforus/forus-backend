<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Households;

use App\Http\Requests\BaseFormRequest;
use App\Models\Identity;
use App\Models\Organization;
use App\Scopes\Builders\IdentityQuery;
use Illuminate\Validation\Rule;

/**
 * @property Organization $organization
 */
class StoreHouseholdMemberRequest extends BaseFormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller
    }

    /**
     * Define the validation rules for the request data.
     *
     * @return array
     */
    public function rules(): array
    {
        $identitiesQuery = IdentityQuery::relatedToOrganization(Identity::query(), $this->organization->id)
            ->select('identities.id');

        return [
            'identity_id' => [
                'required',
                Rule::exists(Identity::class, 'id')
                    ->whereIn('identities.id', $identitiesQuery),
            ],
        ];
    }
}
