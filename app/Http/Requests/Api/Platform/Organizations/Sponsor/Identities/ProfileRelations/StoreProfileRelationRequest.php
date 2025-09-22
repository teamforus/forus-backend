<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Identities\ProfileRelations;

use App\Http\Requests\BaseFormRequest;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\ProfileRelation;
use App\Scopes\Builders\IdentityQuery;
use Illuminate\Validation\Rule;

/**
 * @property Organization $organization
 */
class StoreProfileRelationRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return $this->baseRules(true);
    }

    /**
     * Returns the base validation rules for profile relation records.
     *
     * @param bool $isStore Indicates whether the rules are for a creation or update operation
     * @return array The validation rules
     */
    protected function baseRules(bool $isStore): array
    {
        $identitiesQuery = IdentityQuery::relatedToOrganization(Identity::query(), $this->organization->id)
            ->select('identities.id');

        return [
            ...$isStore ? [
                // Restrict to profiles under the current organization
                'related_identity_id' => [
                    'required',
                    Rule::exists(Identity::class, 'id')
                        ->whereIn('identities.id', $identitiesQuery),
                ],
            ] : [],
            'type' => ['required', Rule::in(ProfileRelation::TYPES)],
            'subtype' => ['required', Rule::in(ProfileRelation::SUBTYPES_BY_TYPE[$this->post('type')] ?? [])],
            'living_together' => ['required', 'boolean'],
        ];
    }
}
